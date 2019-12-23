# Google-MyBusiness-API---LibCal---PHP
This program takes the library hours from the LibCal API and compares with Google MyBusiness data and updates Google MyBusiness with new hours.

This program uses the LibCal API from SpringShare that covers three library locations over four weeks, and a approved Google My Business API from Google Cloud Console.

This program gathers LibCal and Google My Business hours. Then parses the LibCal date and time formats. Then compares the values to the My Business values. If the values are not on Google My Business then the new values are added to a specialHourPeriod array in an object for each library. Afterwards the new specialHours are sent in a patch request to Google My Business for each location.

In addition, this program uses v4 oauth2 authentication. This program has the client_secret.json and the credentials.json files removed. A client_secret.json file can be downloads from the credentials you have created on the Google Cloud Console. This application uses the web application credentials. The credentials.json file will be created in your chosen directory upon your first run of the program.

This is an old prototype of the program. A newer version will be added that adds both Regular and Special Hours to GMB from LibCal.
