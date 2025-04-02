<?php

// make all 4 forms working with real mapping and testing
// clean the temporary error
// use API v2
// form does not send some fields - DONE
// orgniza manage for front user - DONE
// rearrange edit pipedrive data. show activityies under and don't make them editable and show file attachment if exist - DONE



//explain
// Organization works differently. One person can have only one organization. But mupltiple persons can be added in one organization. So I have created a separate page to manage organization. Where you can see all organization and can join and exist any. But in profile page you will see only the organiztion that you have created or you own. Which wil be the last one you join. Please let me know if it is clear

//For create new account checkbox
// I am using a specific value(createAccountWP) as recognizer. So when admin creates checkbox for "create an account" make sure to "createAccountWP" as checkbox value. For existing forms I will set it. But for future forms. Client have to takecare of this. I can also add this info on plugin settings as general instructions. 
// Besides this create account requires an email field as well. I see one of the form(https://promofirenzdev.wpenginepowered.com/prenotazione-sale/) has "Create account" checkbox but not the email field. So we have to add email fields there. Since we are creating person in all forms I am using "Person's email" to create this account. But if you think this can change. Then we can something similar to "createAccountWP". We can use an specific value email field in case user create account like "userEmail"