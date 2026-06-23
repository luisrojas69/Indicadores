"""

DBF → SQL Server Importer

Importa tablas DBF (Visual FoxPro) a SQL Server respetando los tipos de campo nativos.

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



# Lista de tablas específicas a importar.

# Dejar vacío [] para importar TODAS las tablas de la carpeta.
TABLAS_A_IMPORTAR = ["ordenes", "reng_ord"]



CHUNKSIZE = 10_000   # Filas por lote al insertar

ENCODING  = "cp1252" # Encoding de los DBF (Latin-1 / Windows-1252)



# -----------------------------------------

# MAPEO DE TIPOS DBF → SQLAlchemy

# -----------------------------------------

# Referencia de tipos de campo Visual FoxPro:

#   C  → Character  (texto)         → String(length)

#   N  → Numeric    (número)        → Numeric(precision, scale) / Integer

#   F  → Float      (flotante)      → Float

#   L  → Logical    (booleano)      → Boolean

#   D  → Date                       → Date

#   T  → DateTime                   → DateTime

#   I  → Integer                    → Integer

#   B  → Double                     → Float

#   Y  → Currency                   → Numeric(19,4)

#   M  → Memo       (texto largo)   → String (TEXT)

#   G  → General    (OLE/binario)   → LargeBinary

#   P  → Picture    (binario)       → LargeBinary

#   W  → Blob                       → LargeBinary

#   Q  → Varbinary                  → LargeBinary

#   V  → Varchar                    → String(length)



def dbf_field_to_sqlalchemy(field):

    """

    Convierte un campo DBF a su columna SQLAlchemy equivalente,

    respetando longitud, precisión y escala nativos.

    """

    ftype  = field.type.upper()

    length = field.length

    decs   = field.decimal_count



    if ftype == 'C':                          # Character

        return Column(field.name, String(length or 255))



    elif ftype == 'V':                        # Varchar (VFP 9+)

        return Column(field.name, String(length or 255))



    elif ftype == 'N':                        # Numeric

        if decs == 0:

            return Column(field.name, Integer())

        return Column(field.name, Numeric(precision=length, scale=decs))



    elif ftype == 'F':                        # Float

        return Column(field.name, Float())



    elif ftype == 'B':                        # Double

        return Column(field.name, Float(precision=53))



    elif ftype == 'I':                        # Integer (4 bytes)

        return Column(field.name, Integer())



    elif ftype == 'Y':                        # Currency

        return Column(field.name, Numeric(precision=19, scale=4))



    elif ftype == 'L':                        # Logical (boolean)

        return Column(field.name, Boolean())



    elif ftype == 'D':                        # Date

        return Column(field.name, Date())



    elif ftype == 'T':                        # DateTime

        return Column(field.name, DateTime())



    elif ftype == 'M':                        # Memo (texto largo)

        return Column(field.name, String())   # TEXT en SQL Server



    elif ftype in ('G', 'P', 'W', 'Q'):      # Binarios / OLE / Blob

        return Column(field.name, LargeBinary())



    else:

        # Tipo desconocido → varchar seguro

        print(f"  [!] Tipo de campo desconocido '{ftype}' en '{field.name}'. Usando String(255).")

        return Column(field.name, String(255))





# -----------------------------------------

# CONVERSION DE VALORES PYTHON → SQL

# -----------------------------------------



def limpiar_valor(val, ftype):

    """

    Normaliza valores leídos por dbfread para que sean compatibles con

    el tipo SQLAlchemy correspondiente (evita errores de inserción).

    """

    if val is None:

        return None



    ftype = ftype.upper()



    if ftype == 'L':

        if isinstance(val, bool):

            return val

        if isinstance(val, str):

            return val.strip().upper() in ('T', 'Y', '1', 'S')

        return bool(val)



    if ftype == 'D':

        if isinstance(val, datetime.date):

            return val

        return None  # fecha inválida



    if ftype == 'T':

        if isinstance(val, datetime.datetime):

            return val

        return None



    if ftype in ('N', 'F', 'B', 'Y', 'I'):

        if val == '' or val is None:

            return None

        try:

            return val  # dbfread ya devuelve int/float

        except Exception:

            return None



    if ftype in ('G', 'P', 'W', 'Q'):

        if isinstance(val, bytes):

            return val

        return None



    # Texto (C, V, M)

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



        # --- Leer metadatos del DBF ---

        tabla_dbf = DBF(

            str(archivo),

            load=False,

            ignore_missing_memofile=True,

            char_decode_errors="ignore",

            encoding=ENCODING

        )



        # Campos válidos (excluir _NullFlags y similares internos)

        campos = [f for f in tabla_dbf.fields if not f.name.startswith('_')]

        nombres_campos = [f.name for f in campos]

        mapa_tipo = {f.name: f.type for f in campos}



        # --- Crear tabla en SQL Server con tipos correctos ---

        metadata = MetaData()

        columnas_sql = [dbf_field_to_sqlalchemy(f) for f in campos]

        tabla_sql = Table(nombre_tabla, metadata, *columnas_sql)



        with engine.connect() as conn:

            # Eliminar si ya existe

            conn.execute(text(f"IF OBJECT_ID('{nombre_tabla}', 'U') IS NOT NULL DROP TABLE [{nombre_tabla}]"))

            conn.commit()



        metadata.create_all(engine)

        print(f"  Tabla [{nombre_tabla}] creada con {len(campos)} columnas tipadas.")



        # --- Leer y limpiar datos ---

        registros = []

        for fila in tabla_dbf:

            fila_limpia = {}

            for nombre, valor in fila.items():

                if nombre.startswith('_'):

                    continue

                ftype = mapa_tipo.get(nombre, 'C')

                fila_limpia[nombre] = limpiar_valor(valor, ftype)

            registros.append(fila_limpia)



        total_filas = len(registros)

        print(f"  Filas leídas: {total_filas}")



        if total_filas == 0:

            print(f"  Tabla vacía → estructura creada, sin datos.")

            correctos.append(nombre_tabla)

            continue



        # --- Insertar en lotes ---

        with engine.connect() as conn:

            for i in range(0, total_filas, CHUNKSIZE):

                lote = registros[i:i + CHUNKSIZE]

                conn.execute(tabla_sql.insert(), lote)

                print(f"  Insertadas {min(i + CHUNKSIZE, total_filas)}/{total_filas} filas...")

            conn.commit()



        print(f"  OK → [{nombre_tabla}] importada correctamente.")

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