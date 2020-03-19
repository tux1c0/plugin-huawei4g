#!/usr/bin/env python3
from huawei_lte_api.Client import Client
from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Connection import Connection
import json
import sys


if len(sys.argv) == 4:
	ip = sys.argv[1]
	login = sys.argv[2]
	pwd = sys.argv[3]
	
	connection = AuthorizedConnection('http://'+login+':'+pwd+'@'+ip)
	client = Client(connection)

	print(json.dumps(client.device.reboot()))
	client.user.logout()
else:
    print("No parameter has been included")

