
Instructions for setting up the initial API app using the API Platform framework in a local Dev environment follow. 

Make sure docker and composer are installed.   

Create a mysql database. Update the .env.local file with database connection string. Note: localhost will not work. For Macs use host.docker.internal:[port].
DATABASE_URL=mysql://root:@host.docker.internal:3306/db_name?serverVersion=5.7

The same Docker issue arises when working locally and you try to call the ledger API. Add this entry to .env.local
###> ledger ###
LEDGER_ROOT=http://host.docker.internal:8080
###< ledger ###

Navigate to the root directory (where this file is) and run:
docker-compose pull
docker-compose up -d

Running the following command will update the database with the Entity definitions. This wouldn't be run in Production. Ideally the API will work normally with a databaase created from the currently defined SQL schema.  
docker-compose exec php bin/console doctrine:schema:update --force 

Populate the entry_point and warehouses tables in SQL.
INSERT INTO entry_points(id, city, country, delivery_address, name, notes, postal_code, province, street1, street2, active, latitude, longitude, timezone) VALUES('LAXD01', 'COMPTON', 'US', 'BoxC\n921 ARTESIA BLVD\n,COMPTON, CA 90220\nUNITED STATES', 'BoxC', 'Test entry point note', '90220', 'CA', '921 ARTESIA BLVD', '', 1, 34.0522, 118.2437, 'Pacific Standard Time');

INSERT INTO warehouses(id, ep_id, base_fee, city, country, language,language_code,name,notes,peritem_fee, postal_code,province,storage_sm_price, storage_md_price,storage_lg_price,street1,street2,storage_free_days) VALUES('WH0LAX01', 'LAXD01',1.2,'La Puente','US','English','en','US - West 1','Test note.', 0.20, '91746', 'CA', 1.00,2.00,4.00,'BoxC C/O ABC','13936 Valley Blvd',55); 

Navigate to the api directory and run:
php -S 127.0.0.1:8000 -t public

Open the following URL in your browser:
https://127.0.0.1:8443

You should see the API Platform interface that you can use for populating and retrieving test data. You should get an "access denied" message when you attempt to create the test data. That is because authentication is enforced. To use the interface, comment out the access control v2 entry in api/config/packages/security.yml 

Alternately, to query the API with security enabled, using Postman, configure an API Token as follows:
Navigate to the api directory and run bin/console security:encode-password
You will be prompted to enter a password. Create a user in the users table with the encrypted password in the api_token field.



