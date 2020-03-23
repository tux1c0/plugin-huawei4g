PROGRESS_FILE=/tmp/dependancy_huawei4g_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
echo 10 > ${PROGRESS_FILE}
wget -O - https://repository.salamek.cz/deb/salamek.gpg.key|apt-key add -
echo 30 > ${PROGRESS_FILE}
echo "deb     https://repository.salamek.cz/deb/pub all main" | tee /etc/apt/sources.list.d/salamek.cz.list
echo 60 > ${PROGRESS_FILE}
apt-get update
echo 80 > ${PROGRESS_FILE}
apt-get install -y python3-huawei-lte-api
echo 100 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"