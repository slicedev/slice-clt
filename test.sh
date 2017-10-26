#!/bin/bash

export BASE_URI="https://api.slice.com"
export CLIENT_ID="abcd1234"

#If testing for OAuth, Uncomment following line and set appropriate OAuth access token value
#export OAUTH_TOKEN=e2d2157c6ed09be43754d8fb1210

#If using debugging proxy such as Charles, uncomment following line.
#Proxy is expected to run on localhost:8888
#export USE_PROXY=true

#------
# Even thought it does not make sense to provide a username for OAuth and Request token based requests,
# the parameter is required.
# So, set it to a blank string ("")
#
# For some Server-side requests such as the user's collection, set username to be blank string ("")
#------
php slice-clt.php GET /api/v1/users ""

