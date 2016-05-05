# -*- coding: utf-8 -*-
"""
Created on Tue Apr 19 06:38:25 2016

@author: Administrator
"""

import json

from datetime import date, timedelta
today = date.today()

from collections import Counter

import unicodedata

from time import sleep

import mysql
import mysql.connector
import mysql.connector.errors as MySqlErr

import regex as re

import string
punctuation = list(string.punctuation)

from nltk.corpus import stopwords
stop = stopwords.words('spanish')

import csv 
lista_stop_words_spanish = []

with open("stop_words_spanish.csv", "r") as f:    
    reader = csv.reader(f)    
    for line in reader:
        lista_stop_words_spanish.append(line[0])
        
print(lista_stop_words_spanish,"\n")

def pp(o): 
    print(json.dumps(o, indent=1))
    
def quitar_acentos(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])

# las LISTAS de los conceptos que buscamos ++++++++++++++++++++++++++

# esta funcion explicada en cleaning-regex sustituye a preprocess() ¡¡¡¡¡¡¡¡¡¡¡¡
def quitar_acentos(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])
    

# -----  consulta a la tabla de tweets / text ---------------------------------


deltas = (8,7,6,5,4,3,2,1)

#  ¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡  CAMBIAR EL timedelta (1)                              
for delta in deltas:
    
    hashtags = {}
    hashtags["pos"] = {}
    hashtags["neu"] = {}
    hashtags["neg"] = {}
    
    lideres = {}
    lideres["pos"] = {}
    lideres["neu"] = {}
    lideres["neg"] = {}
    
    concepts = {}
    concepts["pos"] = {}
    concepts["neu"] = {}
    concepts["neg"] = {}
    
    urls = {}
    urls["pos"] = {}
    urls["neu"] = {}
    urls["neg"] = {}
    
    texto = {}
    texto["pos"] = {}
    texto["neu"] = {}
    texto["neg"] = {}
    
    yesterday = date.today() - timedelta(delta)                  
    
    for mood in texto:
    
        con = mysql.connector.connect(host='localhost',
                      user='root',
                      passwd='',
                      charset='utf8',
                      database = 'sentiment');
    
        #yesterday = date.today()                 
        cur = con.cursor()
        
        select_str = "select * from tweets where date(created_at) = '" + str(yesterday) +"' AND sentiment ='" + str(mood) + "'"
        cur.execute(select_str)
        
        #  RECOGEMOS los TWEETs PARA SER ANALIZADO de la seleccion de cur.execute
        for row in cur:
            if not(row[1] in texto[mood]):
                texto[mood][row[1]] = ""
            texto_tweet = str(row[8])
            texto[mood][row[1]] += quitar_acentos(re.sub(r'[?|$|.|!|¡|¿|-|,]',r'',texto_tweet).lower())
             
        cur.close()
        con.close()
        
        for sid in texto[mood]:
            lideres[mood][sid] = []
            hashtags[mood][sid] = []
            urls[mood][sid] = []
            concepts[mood][sid] = []
            
        for sid in texto[mood]:
            
            lista_texto = texto[mood][sid].split()
        
            for elemento in lista_texto:   
                if elemento.startswith('@'):
                    lideres[mood][sid].append(elemento)    
                elif elemento.startswith('#'):
                    hashtags[mood][sid].append(elemento)     
                elif elemento.startswith('http'):   
                    urls[mood][sid].append(elemento)   
                else:    
                    # quitamos las stopwords
                    if not elemento in lista_stop_words_spanish:        
                        concepts[mood][sid].append(elemento) 
                    
            print("--------------------    HASHTAGS  ----------------------------- \n")
            count_all_hashtags = Counter()
            #actualizamos el contador  que es como +=
            count_all_hashtags.update(hashtags[mood][sid])
            hashtags_frecuentes = count_all_hashtags.most_common(50)
            print(count_all_hashtags.most_common(50),"\n")
            print(len(count_all_hashtags),"\n")
            
            print("--------------------    LIDERES DE OPINION  ----------------", "\n") 
            count_all_lideres = Counter()
            count_all_lideres.update(lideres[mood][sid])
            lideres_frecuentes = count_all_lideres.most_common(50)
            print(count_all_lideres.most_common(50),  len(count_all_lideres),"\n")
            print(len(count_all_lideres),"\n")
            
            print("--------------------    CONCEPTOS  ---------------------------- \n") 
            count_all_concepts = Counter()
            count_all_concepts.update(concepts[mood][sid])
            concepts_frecuentes = count_all_concepts.most_common(50)
            print(count_all_concepts.most_common(50),"\n")
            print(len(count_all_concepts),"\n")
            
            print("--------------------    URL´S  -------------------------------- \n") 
            count_all_urls = Counter()
            count_all_urls.update(urls[mood][sid])
            urls_frecuentes = count_all_urls.most_common(50)
            print(count_all_urls.most_common(50),"\n")
            print(len(count_all_urls),"\n")
        
            
            concepts_json = json.dumps(concepts_frecuentes)
            lideres_json = json.dumps(lideres_frecuentes)
            hashtags_json = json.dumps(hashtags_frecuentes)
            urls_json = json.dumps(urls_frecuentes)
            
            try:
                conn2 = mysql.connector.connect(user='root', password='', host='localhost',charset='utf8', database='sentiment')
                cursor = conn2.cursor()
            
                anadir_registro = ("INSERT INTO frecuents (search_id, created_at, concepts, leaders, hashtags, urls,  sentiment) VALUES ( %s, %s, %s, %s, %s, %s, %s)")     
                print("la consulta es = ", anadir_registro, "\n") 
                
                print(sid, yesterday)
                registro = (str(sid), yesterday, concepts_json , lideres_json , hashtags_json, urls_json, str(mood))
                print(registro)
                cursor.execute(anadir_registro, registro)    
                conn2.commit()
    
                sleep(5)
                
                cursor.close()
                conn2.close()
                
                print("-----------" + "mood(" + str(mood) + ") sid(" + str(sid) + ")  metido en BASE DE DATOS -------------")          
            
            except mysql.connector.Error as err:
            #    if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            #        print("Something is wrong with your user name or password")
            #    elif err.errno == errorcode.ER_BAD_DB_ERROR:
            #        print("Database does not exist")
            #    else:
                 print(err)