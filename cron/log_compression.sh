#!/bin/bash
source $(dirname "$0")/shellvar

NOW=$(date +"%Y-%m-%d")
LASTMONTHFILEDATE=$(date --date='-30 day' +"%Y-%m-%d")
array=( ${CRONPATH}*.log )
for ((j=0; j<${#array[@]}; j++)); do
  echo 'rename ' ${array[$j]}
  cp ${array[$j]} ${array[$j]}.$NOW
  cp /dev/null ${array[$j]}
done

array=( ${CRONPATH}*.log.$NOW )
for ((j=0; j<${#array[@]}; j++)); do
  echo 'compressing ' ${array[$j]}
  bzip2 -z ${array[$j]}
done

rm -Rf ${CRONPATH}*.${LASTMONTHFILEDATE}.bz2
