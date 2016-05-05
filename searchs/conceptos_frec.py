# -*- coding: utf-8 -*-
"""
Created on Tue Oct 06 16:31:31 2015

@author: portatil
"""

import json

#   ***** tenemos que cambiar el defaultencoding que es ASCII  a utf-8
#import sys
#reload(sys)
#sys.setdefaultencoding("utf-8")

#import datetime
from datetime import date, timedelta
today = date.today()
#print(today)  # formato 2016-03-21

from collections import Counter

import unicodedata        
#import csv
#from collections import Counter

import mysql
import mysql.connector
#from mysql.connector import errorcode

import regex as re
#import json
#from nltk.tokenize import word_tokenize

# -------------    STOPWORDS ---ni RT ni VIA -----------------
#from nltk.corpus import stopwords
import string
punctuation = list(string.punctuation)
#LISTA DE PALABRAS O SIMBOLOS A QUITAR LUEGO RT = retuit, via = mencion
#stop = stopwords.words('spanish') + punctuation + ['RT', 'rt', 'VIA', 'via']

#------------------- stopwords español SIN ACENTOS ---- PARA QUITARLAS LUEGO----
#vamos a importar las NLTK stopwords SIN ACENTOS PARA ELIMINARLAS TAMBIEN AÑADIENDOLAS 
from nltk.corpus import stopwords
stop = stopwords.words('spanish')
 
##print(type(stop))
#string_stop_unicode = " ".join(stop)  # montamos un string UNICODE'
#
## el argumento dentro del join monta una lista con un string unicode
#string_stop_sinacentos_ni_ene = ''.join((c for c in unicodedata.normalize('NFD', string_stop_unicode) if unicodedata.category(c) != 'Mn'))
##print string_stop_sinacentos_ni_ene 
#
## AHORA MONTAMOS LA LISTA DE LA STRING
#list_stop_sin_acentos = string_stop_sinacentos_ni_ene.split()   #lista Unicode
##print list_stop_sin_acentos



import csv 
lista_stop_words_spanish = []

with open("stop_words_spanish.csv", "r") as f:    
    reader = csv.reader(f)    
    for line in reader:
        lista_stop_words_spanish.append(line[0])
        
print(lista_stop_words_spanish,"\n")  

#print(lista_stop_words_spanish)

        

# las LISTAS de los conceptos que buscamos ++++++++++++++++++++++++++
hashtags =[]
lideres =[]
concepts =[]
urls=[]
texto = ""


texto = re.sub(r'[?|$|.|!|¡|¿|-|,]',r'',texto)

# esta funcion explicada en cleaning-regex sustituye a preprocess() ¡¡¡¡¡¡¡¡¡¡¡¡
def quitar_acentos(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])
    

# -----  consulta a la tabla de tweets / text ---------------------------------

con = mysql.connector.connect(host='localhost',
                  user='root',
                  passwd='',
                  charset='utf8',
                  database = 'sentiment');


#  ¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡  CAMBIAR EL timedelta (1)                              
yesterday = date.today() - timedelta(1)                  

#yesterday = date.today()                 
cur = con.cursor()

# en primer lugar seleccionamos los POSITIVOS----------------------------------
#select_str = "select * from tweets where date(created_at) = '" + str(yesterday) +"'"
select_str = "select * from tweets where date(created_at) = '" + str(yesterday) +"' AND sentiment ='pos'"
#print(select_str) 
cur.execute(select_str)

#  RECOGEMOS los TWEETs PARA SER ANALIZADO de la seleccion de cur.execute
for row in cur:
    texto_tweet = str(row[8])
    texto += texto_tweet    
    #print(texto_tweet)
    
    search_id = str(row[1])   # cambiado 
cur.close()
con.close()   

print(search_id)
#print("tipo de texto = ", type(texto))  # texto es una string

print(texto)

# ================  preprocesamos, FALTARIA ELIMINAR LAS STOPWORDS DE LA LISTA  ===

# eliminamos caracteres raros
texto = re.sub(r'[?|$|.|!|¡|¿|-|,]',r'',texto)

def quitar_acentos(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])

    
texto = quitar_acentos(re.sub('<[A-Za-z\/][^>]*>', '', texto.lower()))
lista_texto = texto.split()        
for elemento in lista_texto:   
    if elemento.startswith('@'):
        lideres.append(elemento)    
    elif elemento.startswith('#'):
        hashtags.append(elemento)     
    elif elemento.startswith('http'):   
        urls.append(elemento)   
    else:    
        # quitamos las stopwords
        if not elemento in lista_stop_words_spanish:        
            concepts.append(elemento) 
        
#print(lista_texto,"\n")        
#print(lideres,"\n")    
#print(hashtags,"\n")    
#print(urls,"\n")    
#print(concepts,"\n") 


# ------calculamos los N mas frecuentes 
print("--------------------    HASHTAGS  -------------------------------- \n") 

count_all_hashtags = Counter()
#actualizamos el contador  que es como +=
count_all_hashtags.update(hashtags)
hashtags_frecuentes = count_all_hashtags.most_common(50)
print(count_all_hashtags.most_common(50),"\n")
print(len(count_all_hashtags),"\n")



print("--------------------    LIDERES DE OPINION  --------------------------------", "\n") 

count_all_lideres = Counter()
count_all_lideres.update(lideres)
lideres_frecuentes = count_all_lideres.most_common(50)
print(count_all_lideres.most_common(50),  len(count_all_lideres),"\n")
print(len(count_all_lideres),"\n")


print("-------------  CONCEPTOS  -------------------------------- \n") 

count_all_concepts = Counter()
count_all_concepts.update(concepts)
concepts_frecuentes = count_all_concepts.most_common(50)
print(count_all_concepts.most_common(50),"\n")
print(len(count_all_concepts),"\n")

print("-------------  URL´S  -------------------------------- \n") 

count_all_urls = Counter()
count_all_urls.update(urls)
urls_frecuentes = count_all_urls.most_common(50)
print(count_all_urls.most_common(50),"\n")
print(len(count_all_urls),"\n")

#count_all_lideres.most_common


#--------------------  lo metemos en la tabla FRECUENTS------------

#              nota sobre fechas:
                    #tomorrow = datetime.now().date() + timedelta(days=1)
                    #dateDB = str(datetime.now())    
                    #dateDB = datetime.now().strftime('%Y-%m-%d %H:%M:%S')   

# son LISTAS DE DUPLAS                                 
#concepts_pos= ' '.join(concepts_frecuentes)     
#leaders_pos = ' '.join(lideres_frecuentes)   
#hashtags_pos = ' '.join(hashtags_frecuentes)  

concepts_json = json.dumps(concepts_frecuentes)
lideres_json = json.dumps(lideres_frecuentes)
hashtags_json = json.dumps(hashtags_frecuentes)
urls_json = json.dumps(urls_frecuentes)


#print("concepts_pos")
       
        
try:
    conn2 = mysql.connector.connect(user='root', password='', host='localhost', database='sentiment')
    cursor = conn2.cursor()

    anadir_registro = ("INSERT INTO frecuents (search_id, created_at, concepts, leaders, hashtags, urls,  sentiment) VALUES ( %s, %s, %s, %s, %s, %s, %s)")     
    print("la consulta es = ", anadir_registro, "\n") 
    
    print(search_id, yesterday)
    registro = (search_id, yesterday, concepts_json , lideres_json , hashtags_json, urls_json, 'pos')
    print(registro)
    cursor.execute(anadir_registro, registro)    
    conn2.commit()
    cursor.close()
    conn2.close()
    
    print("-----------  metido en BASE DE DATOS -------------")          

except mysql.connector.Error as err:
#    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
#        print("Something is wrong with your user name or password")
#    elif err.errno == errorcode.ER_BAD_DB_ERROR:
#        print("Database does not exist")
#    else:
     print(err)
            

    
                     
    

