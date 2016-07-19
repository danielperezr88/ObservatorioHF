# -*- coding: utf-8 -*-
"""
Created on Thu May 19 11:48:33 2016

@author: Dani
"""

import os, sys, glob

import importlib
importlib.reload(sys)

import logging, inspect
from dateutil import parser

import json, pickle
import unicodedata
import requests
import regex as re

from time import sleep

import MySQLdb.connections

#import mysql
#import mysql.connector
#from mysql.connector import errorcode

from nltk.tokenize import word_tokenize
from nltk.corpus import stopwords

from sklearn.feature_extraction.text import CountVectorizer

import spanish_corrector as sc
import configanalysis

"""                                 DECORATORS                              """

"""
    @memoize(<function>) (unicodedata)
        Handles function input/output caching.
"""
def memoize(f):
  class memodict(dict):
      __slots__ = ()
      def __missing__(self, key):
          self[key] = ret = f(key)
          return ret
  return memodict().__getitem__

"""                                 UTILITIES                               """

"""
    removeAccents(<string>) -> <string> (unicodedata)
        Returns input string without accents.
"""
def removeAccents(input_str):
    nfkd_form = unicodedata.normalize('NFKD', input_str)
    return u"".join([c for c in nfkd_form if not unicodedata.combining(c)])

"""
    maybeCreateDirs(<list|string>) -> <> (os)
        Creates directories on given paths if they don't exist yet.
"""
def maybeCreateDirs(dirnames):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [dn for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)

"""
    pickleLoad(<string>) -> <mixed> (pickle)
        Handles pickle load from file with given name.
"""
def pickleLoad(filename):
    pickle_file = open(filename, "rb")
    pickleObject = pickle.load(pickle_file)
    pickle_file.close()
    return pickleObject

"""
    pickleDump(<mixed>,<string>) -> <> (os, re, pickle, util:maybeCreateDirs)
        Handles pickle dump of given content on file with given filename.
"""
def pickleDump(pickleObject, filename):
    maybeCreateDirs("/".join(re.split(r'[\\\/]+',filename)[:-1]))
    save_classifier = open(filename,"wb")
    pickle.dump(pickleObject, save_classifier)
    save_classifier.close()
    
"""
    rawLoad(<string>) -> <string> ()
        Loads raw content from file with the given name.
"""
def rawLoad(filename):
    fp = open(filename,'rb')
    result = fp.read().decode()
    fp.close()
    return result

"""
    rawDump(<string>,<string>) -> <> (os, re, util:maybeCreateDirs)
        Saves raw content on a file with the given name.
"""
def rawDump(content, filename):
    maybeCreateDirs("/".join(re.split(r'[\\\/]+',filename)[:-1]))
    fp = open(filename,'wb')
    fp.write(content)
    fp.close()

"""
    loadDependency(<string>,<string>) -> <mixed> (os, re, pickle, util:pickleLoad)
        Loads file based dependencies, wether pickled or raw formatted.
"""
def loadDependency(name,basepath="."):
    filename = os.path.join(basepath,name)
    if not os.path.exists(filename): return False
        
    extension = re.split(r'[\.]+',name)[-1]
    if extension in ('pkl','pickle'):
        return pickleLoad(filename)
    else:
        return rawLoad(filename)
        
"""
    save_pid(<>) -> <> (inspect,logging)
        Saves process id as a file with the name of the script file.
"""
def save_pid():
    """Save pid into a file: filename.pid."""
    try:
        pidfilename = inspect.getfile(inspect.currentframe()) + ".pid"
        f = open(pidfilename, 'w') #It will be removed by index.php if it is not working.
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))

"""                                 FUNCTIONS                               """          

"""

"""
def find_features(document):

    """ Preprocess content """
    #document = quitar_acentos(re.sub(r'[?|$|.|!|¡|¿|,|:|;]',r'',document).lower())
    document = re.sub(r'((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[.\!\/\\w]*))?)',r' ',document).lower()
    document = re.sub(r'[.|!|¡|¿|,|:|;|_]',r' ',document).lower()
    
    """ Tokenize """
    #words = word_tokenize(document.replace('?','ë').replace('_',' '),language="spanish")
    count_vect = CountVectorizer(stop_words=None)
    words = count_vect.build_analyzer().__call__(document.replace('?','ë'))
    
    """ Maybe Spell Correct + Rule-Based Pruning """
    corrected = []
    for each in words:

        """ RULE-BASED PRUNING """

        """ Words not containing usual language characters """
        if not re.search(r'[a-zA-Záéíóúàèìòùîüñ \-]',each):
            continue
        """ URLs """
        if re.match(r'http.*',each):
            continue
        """ RT twitter retweeted special word """
        if each.lower() == 'rt':
            continue
        """ @ twitter user reference special prefix """
        if re.match(r'@.*',each):
            continue
        """ # twitter hashtag special prefix """
        if re.match(r'#.*',each):
            continue
        """ Words containing numbers """
        if re.match(r'[^0-9]*[0-9]',each):
            continue


        """ MAYBE SPELL CORRECT """
        
        """ Condition for Spell Correct: Word contains symbols """
        if re.search(r'[^a-zA-Záéíóúàèìòùîüñ \-]',each):
            corrected.append(sc.correct(each))
        else:
            corrected.append(each)
            
    """ Stop-word Pruning """
    stop_words = set(stopwords.words("spanish"))
    features = [w for w in corrected if w not in stop_words]
    
    """ Filtered features  """
    return features
      
def retrieveClassAndConfidence(classifier,feats=list()):

    if not bool(feats):
        return 'unk', float(0)
        
    probs = classifier.predict_proba([' '.join(feats)])[0]
    result = 'neg' if sorted(range(len(probs)), key=probs.__getitem__, reverse=True)[0] is 0 else 'pos'
    
    return result, (float(probs[1])*2)-1
    
@memoize
def GetGoogleLocation(location):
    ''' Try it at lesast 3 times before desist '''
    for attempt in range(3):
        try:
            r = requests.get('http://maps.googleapis.com/maps/api/geocode/json', params={'address': location})
            jsonDecoded = json.loads(r.content.decode("utf-8") )
            if (len(jsonDecoded["results"]) > 0):
                geoLoc = jsonDecoded["results"][0]["geometry"]["location"]
                return geoLoc["lat"], geoLoc["lng"]
            else:
                return '',''
        except BaseException as e:
            print("Error GetGoogleLocation: %s" % str(e))
            logging.error("Error GeoLocalize: %s" % str(e))
            sleep(1)
        else:
            break
    else:
        return '',''

def GeoLocalize(coordinates, location):
    if (coordinates if coordinates != None else '') != '':
        return coordinates['coordinates'][0], coordinates['coordinates'][1]
        
    if (location if location != None else '') != '':
        return GetGoogleLocation(location.lower())
    
    return '',''
    
def uploadData(searchId, tweet, sentiment, confidence):
    try:
        date_object = parser.parse(tweet['created_at'])
        date_str = date_object.strftime("%Y-%m-%d %H:%M:%S")
        lat, lon = GeoLocalize(tweet['coordinates'], tweet['user']['location'])
        sentimentVal = 0
        if (sentiment == "pos" ):
            sentimentVal = 1
        elif (sentiment == "neg" ):
            sentimentVal = -1

        #conn = mysql.connector.connect(user=configanalysis.dbuser, password=configanalysis.dbpassword,
        #                               host=configanalysis.dbhost, database=configanalysis.dbdatabase)
        
        conn = MySQLdb.connections.Connection(user=configanalysis.dbuser,passwd=configanalysis.dbpassword,
                                          host=configanalysis.dbhost,db=configanalysis.dbdatabase)

        retweetedFrom = ''
        retweetedLat = ''
        retweetedLon = ''
        if ('retweeted_status' in tweet):
            retweetedFrom = tweet['retweeted_status']['user']['screen_name']
            retweetedLat, retweetedLon = GeoLocalize('', tweet['retweeted_status']['user']['location'])
        cursor = conn.cursor()
        anadir_registro = ("INSERT INTO tweets (search_id, created_at, name, id_str, location, lang, geo, text, sentiment, confidence, sentimentVal, geoLat, geoLon, retweetedFrom, retweetedLat, retweetedLon) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")  
        registro = (
                    searchId,
                    date_str,                       # tweet_time
                    tweet['user']['screen_name'],   # tweet_author
                    tweet['user']['id_str'],        # tweet_authod_id
                    tweet['user']['location'],       
                    tweet['lang'],                  # tweet_language
                    tweet['coordinates'],           # tweet_geo
                    tweet['text'],                  # tweet_text
                    '{}'.format(sentiment),         # sentiment
                    confidence,                     # confidence
                    sentimentVal,                   # 1, 0, -1
                    lat,
                    lon,
                    retweetedFrom,                  # retweeted_tweet_author
                    retweetedLat, 
                    retweetedLon
                    )
        cursor.execute(anadir_registro, tuple((reg if reg != None else '') for reg in registro))    
        conn.commit()
        cursor.close()
        conn.close()
        return True
    except BaseException as e:
        print('Failed to upload data: '+ str(e))
        return False

def analizeLine(searchId, line, classifier, word_features):
    if line not in ['\n', '\r\n']:
        try:
            tweet = json.loads(line)
            
            if(tweet['lang']!='es'):
                return True
                
            feats = find_features(tweet['text'])
            sentiment, confidence = retrieveClassAndConfidence(classifier,feats)
            """Save it into db."""
            return uploadData(searchId, tweet, sentiment, confidence)
        except ValueError:
            # TODO: Save it into a file
            print("Error found to be saved:", line)
            return False
        except BaseException as e:
            logging.error('Failed to analizeLine: '+ str(e))
            return False
    return True

def evaluateFiles(classifier, word_features, inputDir, outputDir):
    files = glob.glob(os.path.join(inputDir, "*"))
    for file in files:
        correct = True
        with open(file) as in_file:
            searchId = file.split('_input')[1].split('.')[0]
            for line in in_file:
                correct = correct & analizeLine(searchId,line, classifier, word_features)
            in_file.close()
        if (correct):
            #"""Move used file to output dir."""
            #usedFile = os.path.join(outputDir, os.path.basename(file))
            #os.rename(file, usedFile)
            os.remove(file)
        else:
            errorDir = os.path.join(outputDir, "error")
            maybeCreateDirs(errorDir)
            errorFile = os.path.join(errorDir, os.path.basename(file))
            os.rename(file, errorFile)
            
"""                                   MAIN                                  """

def main():
    """ Create/Retrieve working dirs """
    dirname = os.path.dirname(inspect.getfile(inspect.currentframe()))
    #inputDir = os.path.join(dirname, configanalysis.inputDir)
    inputDir = os.path.join(dirname, "analysis")
    #outputDir = os.path.join(dirname, configanalysis.outputDir)
    outputDir = os.path.join(dirname, "analized")
    maybeCreateDirs([inputDir,outputDir])    
    
    """ Logging configuration """
    basename = os.path.basename(inspect.getfile(inspect.currentframe()))
    logfilename = os.path.join(outputDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.DEBUG, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    """ Process Id file creation """
    save_pid()
    
    """ Dependency retrieval """    
    classifier = loadDependency("class_grid_search.pickle","pickled_algos")
    word_features = sc.words(loadDependency("dictRAE2010_spanish_tilded_notPref_notSuff.txt","pickled_algos"))
    
    """Infinite looop."""
    while True:
        evaluateFiles(classifier, word_features, inputDir, outputDir)
        sleep(2) # delays for 2 seconds

    logging.info('Finished')
    
if __name__ == '__main__':
    main()

