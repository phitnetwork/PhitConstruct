#!/bin/bash

# copymysql.sh

# GENERATED WITH USING ARTUR BODERA'S SCRIPT
# Source script at: https://gist.github.com/2215200

MYSQLDUMP="/usr/bin/mysqldump"
MYSQL="/usr/bin/mysql"

REMOTESERVERIP="138.201.154.237"
REMOTESERVERUSER="phitnetworkUser"
REMOTESERVERPASSWORD="K~4ZCWADMK46*yBGWIrIIrNWk^,aX_X."
REMOTECONNECTIONSTR="-h ${REMOTESERVERIP} -u ${REMOTESERVERUSER} --password=${REMOTESERVERPASSWORD} "

LOCALSERVERIP="localhost"
LOCALSERVERUSER="phitnetworkUser"
LOCALSERVERPASSWORD="K~4ZCWADMK46*yBGWIrIIrNWk^,aX_X."
LOCALCONNECTION="-h ${LOCALSERVERIP} -u ${LOCALSERVERUSER} --password=${LOCALSERVERPASSWORD} "

IGNOREVIEWS=""
MYVIEWS=""
IGNOREDATABASES="select schema_name from information_schema.SCHEMATA where schema_name not like '% %' and  schema_name not like '%-%' and schema_name != 'information_schema' and schema_name != 'mysql' and schema_name != 'performance_schema'  ;"

# GET A LIST OF DATABASES
echo "GET THE DATABASE LIST"
databases=`$MYSQL $REMOTECONNECTIONSTR -e "${IGNOREDATABASES}" | tr -d "| " | grep -v schema_name`

# CREATE NON-EXISTING DATABASES
for db in $databases; do
   echo "create database if not exists $db; "
   #$MYSQL $LOCALCONNECTION --batch -N -e "drop database $db; "
   $MYSQL $LOCALCONNECTION --batch -N -e "create database if not exists $db; "
done

# COPY ALL TABLES
echo "TABLES "$db
for db in $databases; do
    # GET LIST OF TABLES
    tables=`$MYSQL $REMOTECONNECTIONSTR --batch -N -e "select table_name from information_schema.tables where table_name not like '% %' and table_name not like '%-%' and table_type='BASE TABLE' and table_schema='$db';"`
    for table in $tables; do
	echo $db"."$table
        $MYSQLDUMP $REMOTECONNECTIONSTR $IGNOREVIEWS --compress --quick --create-options --extended-insert --lock-tables=false --skip-add-locks --skip-comments --skip-disable-keys --default-character-set=latin1 --skip-triggers --single-transaction  $db $table | mysql $LOCALCONNECTION  $db 
    done  
done


# COPY ALL PROCEDURES
for db in $databases; do
    echo "PROCEDURES "$db
    #PROCEDURES
    $MYSQLDUMP $REMOTECONNECTIONSTR --compress --quick --routines --no-create-info --no-data --no-create-db --skip-opt --skip-triggers $db | \
    sed -r 's/DEFINER=`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | mysql $LOCALCONNECTION  $db 
done

# COPY ALL TRIGGERS
for db in $databases; do
    echo "TRIGGERS "$db
    #TRIGGERS
    $MYSQLDUMP $REMOTECONNECTIONSTR  --compress --quick --no-create-info --no-data --no-create-db --skip-opt --triggers $db | \
    sed -r 's/DEFINER=`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | mysql $LOCALCONNECTION  $db 
done

# COPY ALL VIEWS
for db in $databases; do
    # GET LIST OF ITEMS
    views=`$MYSQL $REMOTECONNECTIONSTR --batch -N -e "select table_name from information_schema.tables where table_name not like '% %' and table_name not like '%-%' and table_type='VIEW' and table_schema='$db';"`
    MYVIEWS=""
    for view in $views; do
        MYVIEWS=${MYVIEWS}" "$view" " 
    done
    echo "VIEWS "$db	
    if [ -n "$MYVIEWS" ]; then
      #VIEWS
      $MYSQLDUMP $REMOTECONNECTIONSTR --compress --quick -Q -f --no-data --skip-comments --skip-triggers --skip-opt --no-create-db --complete-insert --add-drop-table $db $MYVIEWS | \
      sed -r 's/DEFINER=`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g'  | mysql $LOCALCONNECTION  $db  
    fi    
done

echo   "OK!"
