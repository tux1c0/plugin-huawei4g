# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import argparse
import json
import logging
import os
import signal
import sys
import time

from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Client import Client
from huawei_lte_api.exceptions import ResponseErrorNotSupportedException

try:
    from jeedom.jeedom import *
except ImportError:
	print('Error: importing module jeedom.jeedom')
	sys.exit(1)

def handleMessage(client, message):
    if int(message['Smstat']) != 0:
        return

    logging.debug('SMS message : ' + str(message))
    jeedom_com.add_changes('messages::'+str(message['Index']), {'sender' : message['Phone'], 'message' : message['Content']})
    client.sms.set_read(message['Index'])

def checkUnreadMessages(client):
    smsCount = client.sms.sms_count()
    logging.debug('SMS Count : ' + str(smsCount))

    if int(smsCount['LocalUnread']) > 0:
        smsList = client.sms.get_sms_list()
        logging.debug('SMS List : ' + str(smsList))

        if int(smsList['Count']) == 1:
            handleMessage(client, smsList['Messages']['Message'])
        else:
            for message in smsList['Messages']['Message']:
                handleMessage(client, message)

def read_socket(client):
    global JEEDOM_SOCKET_MESSAGE
    if not JEEDOM_SOCKET_MESSAGE.empty():
        logging.debug('Message received in socket JEEDOM_SOCKET_MESSAGE')
        message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
        if message['apikey'] != _apikey:
            logging.error('Invalid apikey from socket : ' + str(message))
            return

        client.sms.send_sms(message['numbers'], message['message']);

def listen():
    jeedom_socket.open()
    logging.debug("Start listening...")

    try:
        while 1:
            time.sleep(_cycle)

            try:
                connection = AuthorizedConnection(_device_url)
                client = Client(connection)
            except Exception as e:
                logging.error('Fail to connect on the device : ' + str(e))
                continue

            try:
                signal = client.monitoring.status()
                jeedom_com.send_change_immediate({'cmd' : 'signal', 'message' : signal['SignalIcon']});
            except Exception as e:
                logging.error('Fail to check signal : ' + str(e))

            try:
                data = client.net.current_plmn()
                jeedom_com.send_change_immediate({'cmd' : 'operatorName', 'message' : data['FullName']});
            except Exception as e:
                logging.error('Fail to check current plmn : ' + str(e))

            try:
                read_socket(client)
            except Exception as e:
                logging.error('Exception on socket : ' + str(e))

            try:
                checkUnreadMessages(client)
            except Exception as e:
                logging.error('Fail to check unread sms : ' + str(e))

            try:
                client.user.logout()
            except ResponseErrorNotSupportedException as e:
                pass
            except Exception as e:
                logging.error('Fail to logout : ' + str(e))
    except KeyboardInterrupt:
        shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))

	try:
		os.remove(_pidfile)
	except:
		pass

	try:
		jeedom_socket.close()
	except:
		pass

	try:
		jeedom_serial.close()
	except:
		pass

	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = 'error'
_socket_port = 55100
_socket_host = 'localhost'
_device_url = 'http://192.168.8.1/'
_pidfile = '/tmp/huawei4gd.pid'
_apikey = ''
_callback = ''
_cycle = 60

parser = argparse.ArgumentParser(description='Huawei 4G Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--deviceurl", help="Device URL", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.socketport:
    _socket_port = int(args.socketport)
if args.deviceurl:
    _device_url = args.deviceurl
if args.pid:
    _pidfile = args.pid
if args.apikey:
    _apikey = args.apikey
if args.callback:
    _callback = args.callback
if args.cycle:
    _cycle = float(args.cycle)

jeedom_utils.set_log_level(_log_level)

logging.info('Start demond')
logging.info('Log level : ' + str(_log_level))
logging.info('Socket port : ' + str(_socket_port))
logging.info('Socket host : ' + str(_socket_host))
logging.info('PID file : ' + str(_pidfile))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))

    jeedom_com = jeedom_com(apikey = _apikey, url = _callback)
    if not jeedom_com.test():
        logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
        shutdown()

    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : ' + str(e))
    shutdown()
