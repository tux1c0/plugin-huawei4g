PROGRESS_FILE=/tmp/dependancy_huawei4g_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
echo 20 > ${PROGRESS_FILE}
echo 40 > ${PROGRESS_FILE}
apt-get install -y python3-pip
echo 60 > ${PROGRESS_FILE}
apt-get install -y python3-setuptools
echo 80 > ${PROGRESS_FILE}
pip3 install typing
echo 90 > ${PROGRESS_FILE}
pip3 install huawei-lte-api
echo 100 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}
service apache2 restart
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"