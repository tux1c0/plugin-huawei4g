#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import json
import sys

def switch_func(apiCall):
    return {
		'api/monitoring/traffic-statistics': client.monitoring.traffic_statistics(),
		'api/user/state-login': client.user.state_login(),
		'api/net/current-plmn': client.net.current_plmn(),
		'api/device/basic_information': client.device.basic_information(),
        'api/net/cell-info': client.device.information(),
		'api/device/signal': client.device.signal(),
		'api/sms/sms-count': client.sms.get_sms_list(),
		'api/device/control': client.device.reboot()
    }.get(apiCall)


if len(sys.argv) == 5:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	api = sys.argv[4]
	
	connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
	client = Client(connection)

	print(json.dumps(switch_func(api)))
else:
    print("No parameter has been included")

client.user.logout()

