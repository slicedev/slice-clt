# SLICE API COMMAND-LINE TOOL

## Initial Setup:

1. clone repo
1. copy private key file to this directory
1. edit test.sh
   1. set PARTNER_CLIENT_ID to your client id
   1. set KEY_FILE to name of your private key file
   1. Add command you would like to execute. The command is of the form
      ```
      php slice-clt.php <HTTP METHOD> <PATH> <USERNAME> ["param" "value"] ["param" "value"]
      ```
   1. When testing eith OAuth token, username is not needed

## Usage Instructions:
Execute test script. e.g.
```
./test.sh
```
