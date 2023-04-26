
pattern=~/httpdocs/deheerhoreca-magento/media/catalog/product/{0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,O,P,Q,R,S,T,U,V,W,X,Y,Z,haha}/*/*.jpg

pattern=~/httpdocs/deheerhoreca-magento/media/catalog/product/haha/*.jpg

DRYRUN=true

for f in ${pattern}
  do
    type=$(identify -format '%[colorspace]' $f);
    if [ "$type" == "CMYK" ]
    then
      echo "${type} ${f} NOK"
      if [ "${DRYRUN}" == false ]
      then
        convert $f -colorspace RGB ./$
        # convert $f -colorspace CMYK -colorspace RGB $f # uncomment this line and comment previous line if previous line is not working.
        echo "$f -colorspace RGB";
      else
        echo "Dryrun: $f -colorspace RGB";
      fi
    else
      echo "${type} ${f} OK"
    fi
  done
