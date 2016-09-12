# -*- coding: utf-8 -*-

from tweepy import Stream
from tweepy import OAuthHandler
from tweepy.streaming import StreamListener
import inspect
import os
import logging
import regex as re
import sys

from dateutil import parser

import json

import MySQLdb.connections as mysqlconn

class MyListener(StreamListener):
    """Custom StreamListener for streaming data."""

    def __init__(self, host, user, db, pwd, sid):
        self.host = host
        self.user = user
        self.db = db
        self.pwd = pwd
        self.sid = sid
        
    def on_data(self, tweet):
        tweet = json.loads(tweet)
            
        try:
        
            date_object = parser.parse(tweet['created_at'])
            date_str = date_object.strftime("%Y-%m-%d %H:%M:%S")

            conn = mysqlconn.Connection(user=self.user, passwd=self.pwd,
                                        host=self.host, db=self.db)
            
            retweetedFrom = ''
            retweetedLat = 0
            retweetedLon = 0
            
            lon, lat = (tweet['coordinates']['coordinates'][0], tweet['coordinates']['coordinates'][1]) if tweet['coordinates'] != None else (0, 0) 
            
            emoji_pattern = re.compile(u"[\u0100-\uFFFF\U0001F000-\U0001F1FF\U0001F300-\U0001F64F\U0001F680-\U0001F6FF\U0001F700-\U0001FFFF\U000FE000-\U000FEFFF]+", flags=re.UNICODE)
            
            if ('retweeted_status' in tweet):
                retweetedFrom = tweet['retweeted_status']['user']['screen_name']
                retweetedLat, retweetedLon = (tweet['retweeted_status']['coordinates']['coordinates'][0], tweet['retweeted_status']['coordinates']['coordinates'][1]) if tweet['retweeted_status']['coordinates'] != None else (0, 0) 
            cursor = conn.cursor()
            
            anadir_registro = ("INSERT INTO tweets (search_id, created_at, name, id_str, location, lang, geo, text, sentiment, confidence, sentimentVal, geoLat, geoLon, retweetedFrom, retweetedLat, retweetedLon, words) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")  
            registro = (
                        self.sid,
                        date_str,                       # tweet_time
                        emoji_pattern.sub(r'',tweet['user']['screen_name']),   # tweet_author
                        tweet['user']['id_str'],        # tweet_authod_id
                        emoji_pattern.sub(r'',str(tweet['user']['location'])),       
                        tweet['lang'],                  # tweet_language,
                        '',
                        emoji_pattern.sub(r'',tweet['text']),                  # tweet_text
                        'unk',
                        0,
                        0,
                        lat,
                        lon,
                        emoji_pattern.sub(r'',retweetedFrom),                  # retweeted_tweet_author
                        retweetedLat, 
                        retweetedLon,
                        ''
                        )

            cursor.execute(anadir_registro, tuple((reg if reg != None else '') for reg in registro))    
            conn.commit()
            cursor.close()
            conn.close()
            
            print('.',end="")
            sys.stdout.flush()
        
        except BaseException as e:
            
            logging.error("Error on_data: %s", str(e))
            print("\n%s (%s)\n"%(str(e), sys.exc_info()[-1].tb_lineno))
            
            return True
        
        return True

    def on_error(self, status):
        print(status)
        return True

def save_pid(pidfilename):
    """Save pid into a file: filename.pid."""
    try:
        f = open(pidfilename, 'w')
        f.write(str(os.getpid()))
        f.close()
    except BaseException as e:
        logging.error('Failed to create pid file: '+ str(e))
                 
def main():
    """Directory handling."""
    dirname = os.path.dirname(os.path.realpath(__file__))
    basename = os.path.basename(os.path.realpath(__file__))
    name_noextension = os.path.splitext(basename)[0]
    
    """Import config file."""
    configinput = __import__("config" + name_noextension)
    
    """Start log."""
    logDir = os.path.join(dirname, "logs")
    if not os.path.exists(logDir):
        os.makedirs(logDir)
    logfilename = os.path.join(logDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.ERROR, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    """PID file."""
    save_pid(inspect.getfile(inspect.currentframe()) + ".pid")
    
    """Curate Keyword List"""
    kw_list = configinput.keyword_list_filter
    if not isinstance(kw_list, list):
        kw_list = [kw_list]

    """Execute the twitter api."""
    try:
        auth = OAuthHandler(configinput.consumer_key, configinput.consumer_secret)
        auth.set_access_token(configinput.access_token, configinput.access_secret)
        listener = MyListener(configinput.dbhost, configinput.dbuser, configinput.db, configinput.dbpwd, configinput.sid)
        twitter_stream = Stream(auth, listener)
        twitter_stream.filter(track=kw_list,languages=['es'])
    except BaseException as e:
        logging.error('Failed to execute twitter api: ' + str(e))    
    
    logging.info('Finished')
    
if __name__ == '__main__':
    main()

