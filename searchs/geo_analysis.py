# -*- coding: utf-8 -*-
"""
Created on Sun Sep 25 11:17:44 2016

@author: Dani
"""

import os
import sys
import pickle
import inspect
import logging
import pandas as pd
import numpy as np
from time import sleep
import matplotlib.path as mplPath

from dateutil import parser
import MySQLdb.connections as mysqlconn

import observatoriohf

from memorize import BucketedMemorize

WDIR = '/var/www/html'
PYDIR = 'searchs'
PICKLEDIR = 'pickled_algos'
basepath = inspect.getfile(inspect.currentframe())
dirname = os.path.dirname(basepath)
basename = os.path.basename(basepath)

with open(os.path.join(WDIR,PYDIR,PICKLEDIR,'SpainMuniPaths.pkl'),'rb') as fp:
    data = pickle.load(fp)

"""
    maybeCreateDirs(<list|string>) -> <> (os)
        Creates directories on given paths if they don't exist yet.
"""
def maybeCreateDirs(dirnames):
    if not isinstance(dirnames,list): dirnames = [dirnames]
    for dirname in [dn for dn in dirnames if dn != '']:
        if not os.path.exists(dirname): os.makedirs(dirname)

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

@BucketedMemorize
def checkPaths(point):
    try:
        for municipality, province, region, country, pths in np.array(data):
            for p in pickle.loads(pths):
                if p.contains_point(point):
                    return ((municipality, province, region, country),True)
        return (None,True)
    except Exception as e:
        return (None,False)

def analyzeTweets(db_date_suffix, limit, lw_id_lim):
    
    records_found = False
    last_reached = False
    last_id = 0
    
    anadir_tabla_geo_extra = ("""CREATE TABLE IF NOT EXISTS `geo_extra_%s` (
        `cnt_id` INT UNSIGNED NOT NULL,
        `municipality` INT UNSIGNED NOT NULL,
        `province` INT UNSIGNED NOT NULL,
        `region` INT UNSIGNED NOT NULL,
        `country` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`cnt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;""")
	
    anadir_tabla_countries = ("""CREATE TABLE IF NOT EXISTS `countries` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` CHAR(128) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
	
    anadir_tabla_regions = ("""CREATE TABLE IF NOT EXISTS `regions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `country_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `name` CHAR(128) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
	
    anadir_tabla_provinces = ("""CREATE TABLE IF NOT EXISTS `provinces` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `country_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `region_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `name` CHAR(128) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
	
    anadir_tabla_municipalities = ("""CREATE TABLE IF NOT EXISTS `municipalities` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `country_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `region_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `province_id` INT UNSIGNED NOT NULL DEFAULT 999999,
        `name` CHAR(128) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;""")
    
    fetch_unclassified = """SELECT t1.cnt_id, t1.geoLat, t1.geoLon
        FROM cnt_extra_%s AS t1 LEFT JOIN geo_extra_%s AS t2 ON t1.cnt_id = t2.cnt_id
        WHERE t2.cnt_id IS NULL AND t1.geoLat <>  0 AND t1.cnt_id > %s LIMIT %s"""%(db_date_suffix,db_date_suffix,lw_id_lim,limit)
	
    add_country = """INSERT INTO countries (name) VALUES (%s)"""
    add_region = """INSERT INTO regions (name,country_id) VALUES (%s,%s)"""
    add_province = """INSERT INTO provinces (name,region_id,country_id) VALUES (%s,%s,%s)"""
    add_municipality = """INSERT INTO municipalities (name,province_id,region_id,country_id) VALUES (%s,%s,%s,%s)"""
	
    select_country = """SELECT id FROM countries WHERE name = %s LIMIT 1"""
    select_region = """SELECT id, country_id FROM regions WHERE name = %s LIMIT 1"""
    select_province = """SELECT id, region_id, country_id FROM provinces WHERE name = %s LIMIT 1"""
    select_municipality = """SELECT id, province_id, region_id, country_id FROM municipalities WHERE name = %s LIMIT 1"""
	
    set_geo_extra = """INSERT INTO geo_extra_""" + db_date_suffix + """ (cnt_id,municipality,province,region,country) VALUES (%s,%s,%s,%s,%s)"""
    
    dbconfig = dict(user=observatoriohf.dbuser,passwd=observatoriohf.dbpassword,
                    host=observatoriohf.dbhost,db=observatoriohf.dbdatabase)
        
    conn = mysqlconn.Connection(**dbconfig)
    cur = conn.cursor()
    
    try:
        
        cur.execute(anadir_tabla_geo_extra%(db_date_suffix,))
        conn.commit()
        cur.execute(anadir_tabla_countries)
        conn.commit()
        cur.execute(anadir_tabla_regions)
        conn.commit()
        cur.execute(anadir_tabla_provinces)
        conn.commit()
        cur.execute(anadir_tabla_municipalities)
        conn.commit()
		
        cur.execute(fetch_unclassified)
        conn.commit()
        
        if cur.rowcount == limit:
            last_reached = True
        
        for cnt_id, lat, lon in [tuple(row) for row in cur]:
            last_id = cnt_id
            res = checkPaths((lat,lon))
            if res is not None:
                
                records_found = True

                conn2 = mysqlconn.Connection(**dbconfig)
                cur2 = conn2.cursor()
                
                municipality, province, region, country = res
                country_id = region_id = province_id = municipality_id = 999999

                try:
					
                    cur2.execute(select_municipality,(municipality,))
                    if cur2.rowcount == 0:
                        conn2.commit()
                        cur2.execute(select_province,(province,))
                        if cur2.rowcount == 0:
                            conn2.commit()
                            cur2.execute(select_region,(region,))
                            if cur2.rowcount == 0:
                                conn2.commit()
                                cur2.execute(select_country,(country,))
                                if cur2.rowcount == 0:
                                    conn2.commit()
                                    cur2.execute(add_country,(country,))
                                    country_id  = cur2.lastrowid
                                else:
                                    country_id = cur2.fetchone()[0]
                                conn2.commit()
                                cur2.execute(add_region,(region,country_id))
                                region_id  = cur2.lastrowid
                            else:
                                region_id, country_id = cur2.fetchone()
                            conn2.commit()
                            cur2.execute(add_province,(province,region_id,country_id))
                            province_id  = cur2.lastrowid
                        else:
                            province_id, region_id, country_id = cur2.fetchone()
                        conn2.commit()
                        cur2.execute(add_municipality,(municipality,province_id,region_id,country_id))
                        municipality_id  = cur2.lastrowid
                    else:
                        municipality_id, province_id, region_id, country_id = cur2.fetchone()
                    conn2.commit()
                    
                    cur2.execute(set_geo_extra,(cnt_id,municipality_id,province_id,region_id,country_id))
                    conn2.commit()
                    
                except Exception as err:
                    logging.error("%s (%s)"%(str(err), sys.exc_info()[-1].tb_lineno))
                    
                cur2.close()
                conn2.close()
                            
    except Exception as err:
        logging.error("%s (%s)"%(str(err), sys.exc_info()[-1].tb_lineno))
                            
    cur.close()
    conn.close()
    
    return last_reached and not records_found, last_id


def main():
    """ Create/Retrieve working dirs """
    logDir = os.path.join(dirname, "geo_analysis")
    maybeCreateDirs([logDir])
    
    """ Logging configuration """
    logfilename = os.path.join(logDir,basename) + ".log"
    logging.basicConfig(filename=logfilename,level=logging.ERROR, format='%(asctime)s %(message)s')
    logging.info('Started')
    
    """ Process Id file creation """
    save_pid()
    
    """Infinite looop."""
    db_date_suffix = parser.datetime.datetime.now().strftime("%Y_%m")
    limit = 100
    lw_id_lim = 0
    while True:
        
        aux = parser.datetime.datetime.now().strftime("%Y_%m")
        if(db_date_suffix != aux): #changed month
            db_date_suffix = aux
            lw_id_lim = 0

        update_lim, last = analyzeTweets(db_date_suffix,limit,lw_id_lim)
        
        if(update_lim): #whether we should update lower id limit or not
            lw_id_lim = last
            
        sleep(0.05) # delays for 0.25 seconds
    
if __name__ == '__main__':
    
    main()
