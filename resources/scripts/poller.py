#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import json
import sys

def switch_func(apiCall):
    return {
        'api/net/cell-info': client.device.information(),
		'api/device/signal': client.device.signal()
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

