#!/bin/bash

# ~/httpdocs/deheerhoreca-magento/shell/cmyk_to_rgb.sh

shopt -s nocaseglob

cd ~/httpdocs/deheerhoreca-magento

# PATTERN=~/httpdocs/deheerhoreca-magento/media/catalog/product/{0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,O,P,Q,R,S,T,U,V,W,X,Y,Z,haha}/*/*.jpg
PATTERN=~/httpdocs/deheerhoreca-magento/media/catalog/product/*/*.jpg

DRYRUN=true

if [ "${DRYRUN}" == true ]; then
  echo "Dryrun: Printing commands only."
fi

echo "Current pattern: ${PATTERN}"
sleep 1

# Subshell, in order to set case insensitive but no risk that the setting will be kept in the interactive session
(shopt -s nocaseglob; 
  for f in ${PATTERN}
    do
      TYPE=$(identify -format '%[colorspace]' "${f}");
      if [ "$TYPE" == "CMYK" ]
      then
        echo "${TYPE} '${f}' NOK"
        if [ "${DRYRUN}" == false ]; then
          convert "${f}" -colorspace RGB ./$
          # convert $f -colorspace CMYK -colorspace RGB $f # uncomment this line and comment previous line if previous line is not working.
          echo "'${f}' -colorspace RGB";
        else
          echo "convert '${f}' -colorspace RGB";
        fi
      else
        # echo "${TYPE} '${f}' OK"
        printf "."
      fi
    done
)
