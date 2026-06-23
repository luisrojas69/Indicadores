"""
DBF → SQL Server Importer (Optimizado con Carga Incremental / Upsert)
"""

from pathlib import Path
from dbfread import DBF
import pandas as pd
from sqlalchemy import create_engine, text, Column, MetaData, Table
from sqlalchemy import (
    String, Integer, Float, Numeric, Boolean,
    DateTime, Date, LargeBinary
)
import datetime

# -----------------------------------------
# CONFIGURACION
# -----------------------------------------
CARPETA_DBF   = r"F:\Respaldo LENOVO\Desktop\3000"
SERVIDOR      = r"LENOVO\SQLEXPRESS"
BASE_DATOS    = "jelcel"
USUARIO       = "sa"
PASSWORD      = "Inicio00**"

# ¡OJO A LA COMA AQUÍ!
TABLAS_A_IMPORTAR = ["ordenes", "reng_ord"]  # Dejar vacío para importar todas las tablas

CHUNKSIZE = 10_000   # Filas por lote al insertar
ENCODING  = "cp1252" # Encoding de los DBF

# -----------------------------------------
# NUEVO: MAPEO DE CLAVES PRIMARIAS
# -----------------------------------------
# SQL necesita saber la clave para hacer la actualización incremental.
# Puedes separar con comas si la tabla tiene clave compuesta (ej: "fact_num, reng_num").
# Si una tabla no está aquí, el script hará un borrado total y carga completa.
CLAVES_PRIMARIAS = {
    "art": "co_art",
    "cat_art": "co_cat",
    "clientes": "co_cli",
    "prov": "co_prov",
    "ordenes": "fact_num",
    "reng_ord": "fact_num, reng_num",
    "factura": "fact_num",
    "reng_fac": "fact_num, reng_num"
}

# -----------------------------------------
# MAPEO DE TIPOS DBF → SQLAlchemy
# -----------------------------------------
def dbf_field_to_sqlalchemy(field):
    ftype  = field.type.upper()
    length = field.length
    decs   = field.decimal_count

    if ftype in ('C', 'V'):
        return Column(field.name, String(length or 255))
    elif ftype == 'N':
        if decs == 0:
            return Column(field.name, Integer())
        return Column(field.name, Numeric(precision=length, scale=decs))
    elif ftype == 'F':
        return Column(field.name, Float())
    elif ftype == 'B':
        return Column(field.name, Float(precision=53))
    elif ftype == 'I':
        return Column(field.name, Integer())
    elif ftype == 'Y':
        return Column(field.name, Numeric(precision=19, scale=4))
    elif ftype == 'L':
        return Column(field.name, Boolean())
    elif ftype in ('D', 'T'):
        return Column(field.name, DateTime() if ftype == 'T' else Date())
    elif ftype == 'M':
        return Column(field.name, String())
    elif ftype in ('G', 'P', 'W', 'Q'):
        return Column(field.name, LargeBinary())
    else:
        return Column(field.name, String(255))

# -----------------------------------------
# CONVERSION DE VALORES PYTHON → SQL
# -----------------------------------------
def limpiar_valor(val, ftype):
    if val is None: return None
    ftype = ftype.upper()

    if ftype == 'L':
        if isinstance(val, bool): return val
        if isinstance(val, str): return val.strip().upper() in ('T', 'Y', '1', 'S')
        return bool(val)
    if ftype == 'D':
        if isinstance(val, datetime.date): return val
        return None
    if ftype == 'T':
        if isinstance(val, datetime.datetime): return val
        return None
    if ftype in ('N', 'F', 'B', 'Y', 'I'):
        if val == '' or val is None: return None
        try: return val
        except Exception: return None
    if ftype in ('G', 'P', 'W', 'Q'):
        if isinstance(val, bytes): return val
        return None
    if isinstance(val, str):
        return val.rstrip('\x00').strip() or None
    return str(val)

# -----------------------------------------
# CONEXION SQL SERVER
# -----------------------------------------
connection_string = (
    f"mssql+pyodbc://{USUARIO}:{PASSWORD}"
    f"@{SERVIDOR}/{BASE_DATOS}"
    "?driver=ODBC+Driver+17+for+SQL+Server"
)
engine = create_engine(connection_string, fast_executemany=True)

# -----------------------------------------
# IMPORTACION
# -----------------------------------------
ruta = Path(CARPETA_DBF)
todos_los_dbfs = [f for f in ruta.iterdir() if f.suffix.lower() == '.dbf']

if TABLAS_A_IMPORTAR:
    tablas_lower = {t.lower() for t in TABLAS_A_IMPORTAR}
    dbfs = [f for f in todos_los_dbfs if f.stem.lower() in tablas_lower]
else:
    dbfs = todos_los_dbfs

print(f"DBF a procesar: {len(dbfs)}")

correctos = []
errores   = []

for archivo in dbfs:
    try:
        nombre_tabla = archivo.stem.lower()
        print(f"\nProcesando {archivo.name}...")

        tabla_dbf = DBF(
            str(archivo), load=False, ignore_missing_memofile=True,
            char_decode_errors="ignore", encoding=ENCODING
        )

        campos = [f for f in tabla_dbf.fields if not f.name.startswith('_')]
        mapa_tipo = {f.name: f.type for f in campos}

        metadata = MetaData()
        columnas_sql = [dbf_field_to_sqlalchemy(f) for f in campos]
        tabla_sql = Table(nombre_tabla, metadata, *columnas_sql)

        # Buscar clave primaria
        clave_primaria = CLAVES_PRIMARIAS.get(nombre_tabla)

        # Leer registros a memoria
        registros = []
        for fila in tabla_dbf:
            fila_limpia = {}
            for nombre, valor in fila.items():
                if nombre.startswith('_'): continue
                ftype = mapa_tipo.get(nombre, 'C')
                fila_limpia[nombre] = limpiar_valor(valor, ftype)
            registros.append(fila_limpia)

        total_filas = len(registros)
        print(f"  Filas leídas: {total_filas}")

        if total_filas == 0:
            print(f"  Tabla vacía, se omite inserción.")
            correctos.append(nombre_tabla)
            continue

        # Usamos engine.begin() para que maneje la transacción automáticamente
        with engine.begin() as conn:
            tabla_existe = conn.execute(text(f"SELECT OBJECT_ID('{nombre_tabla}', 'U')")).scalar() is not None

            # Si la tabla NO existe, o no le configuramos clave primaria: MODO CARGA COMPLETA
            if not tabla_existe or not clave_primaria:
                conn.execute(text(f"IF OBJECT_ID('{nombre_tabla}', 'U') IS NOT NULL DROP TABLE [{nombre_tabla}]"))
                tabla_sql.create(conn)
                print(f"  Modo: Carga Completa (Se recreó la tabla).")
                
                for i in range(0, total_filas, CHUNKSIZE):
                    lote = registros[i:i + CHUNKSIZE]
                    conn.execute(tabla_sql.insert(), lote)
                    print(f"  Insertadas {min(i + CHUNKSIZE, total_filas)}/{total_filas} filas...")

            # Si la tabla SÍ existe y tiene clave primaria: MODO CARGA INCREMENTAL
            else:
                tabla_temp_nombre = f"{nombre_tabla}_staging"
                conn.execute(text(f"IF OBJECT_ID('{tabla_temp_nombre}', 'U') IS NOT NULL DROP TABLE [{tabla_temp_nombre}]"))
                
                tabla_temp = Table(tabla_temp_nombre, metadata, *[dbf_field_to_sqlalchemy(f) for f in campos])
                tabla_temp.create(conn)
                print(f"  Modo: Carga Incremental activada sobre '{clave_primaria}'.")

                # 1. Enviar los datos del DBF a la tabla temporal (Staging)
                for i in range(0, total_filas, CHUNKSIZE):
                    lote = registros[i:i + CHUNKSIZE]
                    conn.execute(tabla_temp.insert(), lote)

                # 2. Borrar en la tabla real los registros que vamos a actualizar
                claves = [c.strip() for c in clave_primaria.split(',')]
                condicion_join = " AND ".join([f"D.[{c}] = O.[{c}]" for c in claves])
                
                sql_delete = text(f"""
                    DELETE D
                    FROM [{nombre_tabla}] D
                    INNER JOIN [{tabla_temp_nombre}] O ON {condicion_join}
                """)
                conn.execute(sql_delete)

                # 3. Insertar todo el contenido (tanto nuevos como actualizados) desde la temporal
                nombres_cols = ", ".join([f"[{f.name}]" for f in campos])
                sql_insert = text(f"""
                    INSERT INTO [{nombre_tabla}] ({nombres_cols})
                    SELECT {nombres_cols} FROM [{tabla_temp_nombre}]
                """)
                conn.execute(sql_insert)

                # 4. Limpiar basura temporal
                conn.execute(text(f"DROP TABLE [{tabla_temp_nombre}]"))
                print(f"  Actualización exitosa. Registros nuevos y modificados aplicados.")

        correctos.append(nombre_tabla)

    except Exception as e:
        print(f"  ERROR → {archivo.name}: {e}")
        errores.append((archivo.name, str(e)))

# -----------------------------------------
# RESUMEN FINAL
# -----------------------------------------
print("\n" + "=" * 40)
print("      IMPORTACION FINALIZADA")
print("=" * 40)
print(f"  Tablas importadas : {len(correctos)}")
print(f"  Errores           : {len(errores)}")

if errores:
    print("\n  DETALLE DE ERRORES:")
    for tabla, error in errores:
        print(f"  - {tabla} → {error}")