PROGRESS_FILE=/tmp/dependancy_huawei4g_in_progress
if [ ! -z $1 ]; then
        PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "* Installation des dépendances *"
echo "********************************************************"
wget -O - https://repository.salamek.cz/deb/salamek.gpg.key|apt-key add -
echo 10 > ${PROGRESS_FILE}
echo "deb https://repository.salamek.cz/deb/pub all main" | tee /etc/apt/sources.list.d/salamek.cz.list
echo 20 > ${PROGRESS_FILE}
apt-get update
echo 30 > ${PROGRESS_FILE}
apt-get install -y python3-dicttoxml python3-xmltodict python3-requests python3-setuptools
echo 40 > ${PROGRESS_FILE}
apt-get install -y python3-huawei-lte-api
echo 50 > ${PROGRESS_FILE}
RES=$( dpkg-query -l | grep huawei-lte-api | wc -l )
echo $RES
if [ $RES -gt 0 ]; then
        echo "Installation OK"
else
        echo "Installation failed, trying to copy locally."
        cd "$(dirname "$0")"
        cd ../3rdparty/huawei-lte-api
        pwd
        echo 60 > ${PROGRESS_FILE}
        python3 setup.py install
fi
echo 100 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}
echo "********************************************************"
echo "* Installation terminée *"
echo "********************************************************"