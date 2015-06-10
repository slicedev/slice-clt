# SLICE API COMMAND-LINE TOOL

## Initial Setup:

1. Put `slice-clt.php` and `api_functions.php` in a new empty directory.
1. Update the three configs at the top of "slice-clt.php":
  - Set client_id to be the client id provided by Slice.
  - Set the Base URL to the server name you have been using for development, or that
   has been provided by Slice (must match client_id).
  - Usually you will leave the locale as "en_US".
1. Create a new file in the same directory called "myprivatekey.pem", which contains the
   private key you generated when being provisioned (must match client_id, and must match
   the public key Slice has recorded for you).
   


## Usage Instructions:

Invoke the script in the following way:

  `php slice-clt.php METHOD PATH USERNAME [PARAM1 VALUE1 [PARAM2 VALUE2 ... ]]`

e.g.

  `php slice-clt.php GET /api/v1/orders vpo_test2 limit 10`

The script will output the full request including signature string, and the JSON response
that came back from the server.
