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

	list = [json.dumps(client.user.state_login())]
	list.append(json.dumps(client.monitoring.traffic_statistics()))
	list.append(json.dumps(client.net.current_plmn()))
	list.append(json.dumps(client.device.basic_information()))
	list.append(json.dumps(client.device.information()))
	list.append(json.dumps(client.device.signal()))
	list.append(json.dumps(client.sms.get_sms_list()))
	
	client.user.logout()
	
	print(list)
else:
    print("No parameter has been included")

