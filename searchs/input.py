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
        
        db_date_suffix = parser.datetime.datetime.now().strftime("%Y_%m")
        
        try:
            conn = mysqlconn.Connection(user=user, passwd=pwd, host=host, db=db)
            cursor = conn.cursor()
            
            anadir_tabla_pht_scraped = ("""CREATE TABLE IF NOT EXISTS `pht_scraped_%s` (
              `pht_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `cnt_id` INT UNSIGNED NOT NULL,
              `url` TEXT NOT NULL DEFAULT '',
              `url_is_available` BIT(1) NOT NULL DEFAULT 0,
              `file_path` TEXT NOT NULL DEFAULT '',
              `file_is_available` BIT(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`pht_id`),
              KEY `cnt_id` (`cnt_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_pht_extra = ("""CREATE TABLE IF NOT EXISTS `pht_extra_%s` (
              `pht_id` INT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL,
              `geolat` FLOAT NOT NULL DEFAULT 0,
              `geolon` FLOAT NOT NULL DEFAULT 0,
              `gender` TINYINT NOT NULL DEFAULT 0,
              `age` TINYINT UNSIGNED NOT NULL DEFAULT 255,
              `is_multiface` BIT(1) NOT NULL DEFAULT 0,
              `mood` TINYINT NOT NULL DEFAULT 0,
              `environment` CHAR(35) NOT NULL DEFAULT '',
              PRIMARY KEY (`pht_id`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_pht_inferred = ("""CREATE TABLE IF NOT EXISTS `pht_whats_inferred_%s` (
              `pht_id` INT UNSIGNED NOT NULL,
              `geo` BIT(1) NOT NULL DEFAULT 0,
              `gender` BIT(1) NOT NULL DEFAULT 0,
              `age` BIT(1) NOT NULL DEFAULT 0,
              `is_multiface` BIT(1) NOT NULL DEFAULT 0,
              `mood` BIT(1) NOT NULL DEFAULT 0,
              `environment` BIT(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`pht_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_scraped = ("""CREATE TABLE IF NOT EXISTS `cnt_scraped_%s` (
              `cnt_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `content` TEXT NOT NULL DEFAULT '',
              `id_str` CHAR(35) NOT NULL DEFAULT '',
              PRIMARY KEY (`cnt_id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_info = ("""CREATE TABLE IF NOT EXISTS `cnt_info_%s` (
              `cnt_id` INT UNSIGNED NOT NULL,
              `scraped_at` DATETIME NOT NULL,
              `search_id` MEDIUMINT UNSIGNED NOT NULL,
              `origin_id` INT UNSIGNED NOT NULL,
              `user_id_str` CHAR(30) NOT NULL DEFAULT '',
              `user_name` CHAR(30) NOT NULL DEFAULT '',
              `location` CHAR(30) NOT NULL DEFAULT '',
              PRIMARY KEY (`cnt_id`),
              KEY `origin_id` (`origin_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_interactions = ("""CREATE TABLE IF NOT EXISTS `cnt_interactions_%s` (
              `cnt_id` INT UNSIGNED NOT NULL,
              `original_from` CHAR(30) NOT NULL DEFAULT '',
              `original_from_id_str` CHAR(30) NOT NULL DEFAULT '',
              `original_lat` FLOAT NOT NULL DEFAULT 0,
              `original_lon` FLOAT NOT NULL DEFAULT 0,
              `original_location` CHAR(30) NOT NULL DEFAULT '',
              `shared_count` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
              `reacted_count` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
              `reacted_pos_count` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
              `reacted_neg_count` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`cnt_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_inferred = ("""CREATE TABLE IF NOT EXISTS `cnt_whats_inferred_%s` (
              `cnt_id` INT UNSIGNED NOT NULL,
              `created_at` BIT(1) NOT NULL DEFAULT 0,
              `geo` BIT(1) NOT NULL DEFAULT 0,
              `original_geo` BIT(1) NOT NULL DEFAULT 0,
              `gender` BIT(1) NOT NULL DEFAULT 0,
              `age` BIT(1) NOT NULL DEFAULT 0,
              `subjectivism` BIT(1) NOT NULL DEFAULT 0,
              `polarity` BIT(1) NOT NULL DEFAULT 0,
              `lang` BIT(1) NOT NULL DEFAULT 0,
              `business_field` BIT(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`cnt_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            anadir_tabla_extra = ("""CREATE TABLE IF NOT EXISTS `cnt_extra_%s` (
              `cnt_id` INT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL,
              `geoLat` FLOAT NOT NULL DEFAULT 0,
              `geoLon` FLOAT NOT NULL DEFAULT 0,
              `gender` TINYINT NOT NULL DEFAULT 0,
              `age` TINYINT UNSIGNED NOT NULL DEFAULT 255,
              `subjectivism` TINYINT NOT NULL DEFAULT 0,
              `polarity` TINYINT NOT NULL DEFAULT 0,
              `lang` CHAR(35) NOT NULL DEFAULT 'und',
              `business_field` CHAR(35) NOT NULL DEFAULT 'und',
              PRIMARY KEY (`cnt_id`),
              KEY `created_at_and_polarity` (`created_at`,`polarity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
            
            conn.autocommit(False)
            cursor.execute(anadir_tabla_pht_scraped%(db_date_suffix,))
            cursor.execute(anadir_tabla_pht_extra%(db_date_suffix,))
            cursor.execute(anadir_tabla_pht_inferred%(db_date_suffix,))
            cursor.execute(anadir_tabla_scraped%(db_date_suffix,))
            cursor.execute(anadir_tabla_info%(db_date_suffix,))
            cursor.execute(anadir_tabla_extra%(db_date_suffix,))
            cursor.execute(anadir_tabla_inferred%(db_date_suffix,))
            cursor.execute(anadir_tabla_interactions%(db_date_suffix,))
            conn.commit()
            
            cursor.close()
            conn.close()
            
        except BaseException as e:
            
            logging.error("Error on_data: %s", str(e))
            print("\n%s (%s)\n"%(str(e), sys.exc_info()[-1].tb_lineno))
            conn.rollback()
        
    def on_data(self, tweet):
        tweet = json.loads(tweet)
            
        try:
        
            date_object = parser.parse(tweet['created_at'])
            date_str = date_object.strftime("%Y-%m-%d %H:%M:%S")
            now_object = parser.datetime.datetime.now()
            now_str = now_object.strftime("%Y-%m-%d %H:%M:%S")
            db_date_suffix = now_object.strftime("%Y_%m")
            
            emoji_pattern = re.compile(u"[\u0100-\uFFFF\U0001F000-\U0001F1FF\U0001F300-\U0001F64F\U0001F680-\U0001F6FF\U0001F700-\U0001FFFF\U000FE000-\U000FEFFF]+", flags=re.UNICODE)

            content = emoji_pattern.sub(r'',tweet['text'])
            id_str = tweet['id_str']

            user_name = emoji_pattern.sub(r'',tweet['user']['screen_name'])
            user_id_str = tweet['user']['id_str']
            user_img = tweet['user']['profile_image_url']            

            location = emoji_pattern.sub(r'',str(tweet['user']['location']))

            lang = tweet['lang']
            
            lon, lat = (tweet['coordinates']['coordinates'][0], tweet['coordinates']['coordinates'][1]) if tweet['coordinates'] != None else (0, 0) 

            retweetedFrom = ''
            retweetedFromstr = ''
            retweetedLoc = ''
            retweetedLat = 0
            retweetedLon = 0
            rt_count = 0
            
            is_retweet = 'retweeted_status' in tweet
            
            if is_retweet:
                retweetedFrom = emoji_pattern.sub(r'',tweet['retweeted_status']['user']['screen_name'])
                retweetedLoc = emoji_pattern.sub(r'',str(tweet['retweeted_status']['user']['location']))
                retweetedFromstr = tweet['retweeted_status']['user']['id_str']
                retweetedLat, retweetedLon = (tweet['retweeted_status']['coordinates']['coordinates'][0], tweet['retweeted_status']['coordinates']['coordinates'][1]) if tweet['retweeted_status']['coordinates'] != None else (0, 0) 
                rt_count = tweet['retweet_count']
            
            fav_count = tweet['favorite_count']
            
            conn = mysqlconn.Connection(user=self.user, passwd=self.pwd,
                                        host=self.host, db=self.db)
            cursor = conn.cursor()
            
            anadir_contenido = ("INSERT INTO " + 'cnt_scraped_' + db_date_suffix + " (content, id_str) VALUES (%s, %s)")
            cursor.execute(anadir_contenido,tuple((reg if reg != None else '') for reg in (content,id_str)))
            cnt_id = cursor.lastrowid
            conn.commit()
            
            buscar_origen = ("SELECT origin_id FROM origins WHERE origin = %s LIMIT 1")
            cursor.execute(buscar_origen,('twitter',))
            if cursor.rowcount == 0:
                conn.commit()
                anadir_origen = ("INSERT INTO origins (origin) VALUES (%s)")
                cursor.execute(anadir_origen,('twitter',))
                orig_id = cursor.lastrowid
            else:
                orig_id = cursor.fetchone()[0]
                conn.commit()
                
            anadir_info = ("INSERT INTO " + 'cnt_info_' + db_date_suffix + " (cnt_id, scraped_at, search_id, origin_id, user_id_str, user_name, location) VALUES (%s,%s,%s,%s,%s,%s,%s)")
            cursor.execute(anadir_info, tuple((reg if reg != None else '') for reg in (cnt_id, now_str, self.sid, orig_id, user_id_str, user_name, location)))
            conn.commit()
            
            anadir_extra = ("INSERT INTO " + 'cnt_extra_' + db_date_suffix + " (cnt_id, created_at, geoLat, geoLon, lang) VALUES (%s, %s, %s, %s, %s)")
            cursor.execute(anadir_extra,tuple((reg if reg != None else '') for reg in (cnt_id, date_str, lat, lon, lang)))
            conn.commit()
            
            anadir_interactions = ("INSERT INTO " + 'cnt_interactions_' + db_date_suffix + " (cnt_id, original_from, original_from_id_str, original_lat, original_lon, original_location, shared_count, reacted_count, reacted_pos_count) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)")
            cursor.execute(anadir_interactions,tuple((reg if reg != None else '') for reg in (cnt_id, retweetedFrom, retweetedFromstr, retweetedLat, retweetedLon, retweetedLoc, rt_count, rt_count+fav_count, fav_count)))
            conn.commit()
            
            anadir_inferred = ("INSERT INTO " + 'cnt_whats_inferred_' + db_date_suffix + " (cnt_id, geo, original_geo, lang) VALUES (%s, %s, %s, %s)")
            cursor.execute(anadir_inferred,tuple((reg if reg != None else '') for reg in (cnt_id, int(any([lat!=0,lon!=0])), int(any([retweetedLat!=0,retweetedLon!=0])), lang)))
            conn.commit()
            
            if(user_img != ''):
                
                user_img = re.sub(r'\_normal\.jpg',r'.jpg',user_img)
                user_img = re.sub(r'\_normal\.jpeg',r'.jpeg',user_img)
                
                anadir_img = ("INSERT INTO "+ 'pht_scraped_' + db_date_suffix + " (cnt_id, url, url_is_available) VALUES (%s, %s, %s)")
                cursor.execute(anadir_img,tuple((reg if reg != None else '') for reg in (cnt_id, user_img, 1)))
                conn.commit()
                pht_id = cursor.lastrowid
                
                anadir_img = ("INSERT INTO "+ 'pht_extra_' + db_date_suffix + " (pht_id, created_at) VALUES (%s, %s)")
                cursor.execute(anadir_img,tuple((reg if reg != None else '') for reg in (pht_id, date_str)))
                conn.commit()
                
                anadir_img = ("INSERT INTO "+ 'pht_whats_inferred_' + db_date_suffix + " (pht_id) VALUES (%s)")
                cursor.execute(anadir_img,tuple((reg if reg != None else '') for reg in (pht_id,)))
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

