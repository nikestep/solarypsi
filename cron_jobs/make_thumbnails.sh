#!/bin/sh
# Set MySQL connection variables
MYSQL_DB=dbname
MYSQL_USER=dbuser
MYSQL_PASSWORD=dbpassword

# Read in all images that need thumbails generated for them
read -ra IMAGES_ARR <<< $(mysql -D${MYSQL_DB} -u${MYSQL_USER} -p${MYSQL_PASSWORD} -se "SELECT id, path, thumb_width, thumb_height FROM images_to_convert")

# Calculate number of records to process
RECORD_COUNT=$((${#IMAGES_ARR[@]}/4))

# Process each record
for i in $(seq 0 $(($RECORD_COUNT - 1))); do
    convert ${IMAGES_ARR[$((i * 4 + 1))]} -resize ${IMAGES_ARR[$((i * 4 + 2))]}x${IMAGES_ARR[$((i * 4 + 3))]} ${IMAGES_ARR[$((i * 4 + 1))]}._thumb.jpg
    mysql -D${MYSQL_DB} -u${MYSQL_USER} -p${MYSQL_PASSWORD} -se "INSERT INTO cron_log VALUES('Thumbnail Create', NOW(), 'Success', '${IMAGES_ARR[$((i * 4 + 1))]}._thumb.jpg')"
    mysql -D${MYSQL_DB} -u${MYSQL_USER} -p${MYSQL_PASSWORD} -se "DELETE FROM images_to_convert WHERE id=${IMAGES_ARR[$((i * 4 + 0))]}"
done