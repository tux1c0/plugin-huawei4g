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

class switch(object):
    def __init__(self, value):
        self.value = value
        self.fall = False

    def __iter__(self):
        """Return the match method once, then stop"""
        yield self.match
        raise StopIteration
    
    def match(self, *args):
        """Indicate whether or not to enter a case suite"""
        if self.fall or not args:
            return True
        elif self.value in args:
            self.fall = True
            return True
        else:
            return False

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
	while not JEEDOM_SOCKET_MESSAGE.empty():
		logging.debug('Message received in socket JEEDOM_SOCKET_MESSAGE')
		message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
		if message['apikey'] != _apikey:
			logging.error('Invalid apikey from socket: ' + str(message))
			return

		try:
			for case in switch(message['action']):
				if case("sendsms"):
					client.sms.send_sms(message['numbers'], message['message'])
					break
				if case("reboot"):
					client.device.reboot()
					break
				if case("enabledata"):
					client.dial_up.set_mobile_dataswitch(1)
					break
				if case("disabledata"):
					client.dial_up.set_mobile_dataswitch(0)
					break
				if case("delsms"):
					client.sms.delete_sms(message['index'])
					break
				if case():
					logging.error('Invalid action from socket')
		except Exception as e:
			logging.error('Failed to perform an action: ' + str(e))

def listen():
	jeedom_socket.open()
	logging.debug("Start listening...")

	try:
		while 1:
			time.sleep(_cycle)

			if len(_devices) == 0:
				continue

			for key in _devices:
				if _devices[key]['username'] and _devices[key]['password']:
					_device_url = 'http://' + _devices[key]['username'] + ':' + _devices[key]['password'] + '@' + _devices[key]['ip']
				else:
					_device_url = 'http://' + _devices[key]['ip']
				logging.debug('URL : ' + _device_url)
				break

			try:
				connection = AuthorizedConnection(_device_url)
				client = Client(connection)
				jeedom_com.send_change_immediate({'cmd' : 'status', 'data' : 'Up'})
			except Exception as e:
				jeedom_com.send_change_immediate({'cmd' : 'status', 'data' : 'Down'})
				logging.error('Failed to connect on the device: ' + str(e))
				continue

			try:
				read_socket(client)
			except Exception as e:
				logging.error('Exception on socket : ' + str(e))
				continue

			try:
				signal = client.monitoring.status()
				jeedom_com.send_change_immediate({'cmd' : 'signal', 'message' : signal['SignalIcon']})
			except Exception as e:
				logging.error('Failed to check signal: ' + str(e))
				continue

			try:
				data = client.net.current_plmn()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check current_plmn: ' + str(e))
				continue

			try:
				data = client.monitoring.traffic_statistics()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check traffic_statistics: ' + str(e))
				continue

			try:
				data = client.device.basic_information()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check basic_information: ' + str(e))
				continue

			try:
				data = client.device.information()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check information: ' + str(e))
				continue

			try:
				data = client.device.signal()
				jeedom_com.send_change_immediate({'cmd' : 'signal', 'data' : data})
			except Exception as e:
				logging.error('Failed to check signal: ' + str(e))
				continue

			try:
				data = client.monitoring.month_statistics()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check month_statistics: ' + str(e))
				continue

			try:
				data = client.dial_up.mobile_dataswitch()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check mobile_dataswitch: ' + str(e))
				continue

			try:
				data = client.wlan.status_switch_settings()
				jeedom_com.send_change_immediate({'cmd' : 'radio', 'data' : data['radios']})
			except Exception as e:
				logging.error('Failed to check status_switch_settings: ' + str(e))
				continue

			try:
				data = client.wlan.multi_basic_settings()
				jeedom_com.send_change_immediate({'cmd' : 'ssid', 'data' : data['Ssids']})
			except Exception as e:
				logging.error('Failed to check multi_basic_settings: ' + str(e))
				continue

			try:
				data = client.sms.sms_count()
				jeedom_com.send_change_immediate({'cmd' : 'update', 'data' : data})
			except Exception as e:
				logging.error('Failed to check sms_count: ' + str(e))
				continue

			try:
				data = client.sms.get_sms_list()
				jeedom_com.send_change_immediate({'cmd' : 'smsList', 'data' : data})
			except Exception as e:
				logging.error('Failed to check get_sms_list: ' + str(e))
				continue

			try:
				checkUnreadMessages(client)
			except Exception as e:
				logging.error('Failed to check unread sms : ' + str(e))
				continue

			try:
				client.user.logout()
			except ResponseErrorNotSupportedException as e:
				pass
			except Exception as e:
				logging.error('Failed to logout : ' + str(e))
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
_pidfile = '/tmp/huawei4gd.pid'
_apikey = ''
_callback = ''
_cycle = 60

parser = argparse.ArgumentParser(description='Huawei 4G Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.socketport:
    _socket_port = int(args.socketport)
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
        logging.error('Network communication issues. Please fix your Jeedom network configuration.')
        shutdown()

    _devices = jeedom_com.get_devices_list()
    logging.debug(_devices)
    if not _devices:
        shutdown()

    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : ' + str(e))
    shutdown()
