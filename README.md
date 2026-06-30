# System-for-Client-Transactions
A  web-based system designed to help an office to manage client data and assistance records efficiently. The system allows authorized users to record, view, update, and track client information in one centralized platform.

# System Pictures
  i. **_Sign-in Page_**
  <img width="1920" height="906" alt="1000023014" src="https://github.com/user-attachments/assets/39379e88-6c09-4cd9-b95e-a995537ad271" />


  ii.**_Verification Page_**
  <img width="1920" height="876" alt="1000023008" src="https://github.com/user-attachments/assets/de31feca-2632-499d-9343-07d27df20bdf" />


  iii. **_Dashboard Page_**
  <img width="1918" height="870" alt="1000023009" src="https://github.com/user-attachments/assets/e08190e2-d4e7-4dce-9149-6d25bf1b3976" />

  
  iv. **_Service Records Page_**
  <img width="1912" height="824" alt="1000023004" src="https://github.com/user-attachments/assets/30009a99-c865-4a1d-87a9-1a6d481f0ff1" />


  v. **_Client Records Page_**
  <img width="1918" height="874" alt="1000023005" src="https://github.com/user-attachments/assets/422e997f-e744-430f-830e-c9bc7335a2b3" />


  vi. **_Entry Page_**
  <img width="1918" height="878" alt="1000023006" src="https://github.com/user-attachments/assets/eddd899e-14f6-4e9b-a176-a66d952fef0b" />
 

# Features:
**a. Sign In/Sign Up page**
- Emails admin about sign-up requests and can approve by clicking a link
- User can change their password if they have already forgotten their existing password
- There is 2-Factor Authentication, and a one-time PIN comes from Google Authenticator that works either offline or online


**b. Dashboard**
- Shows the graphs of the existing clients based on civil status, age, assistance, gender, and barangay
- The graphs could be filtered based on the type of assistance, barangay, age, and the date of the transactions
- For admin accounts, the user has 3 additional features: oversee the edit and deletion requests, see the system logs, and download backup


**c. Service Records Page**
- Lists all the transactions by name and includes other necessary information like the address of the client, gender, age, assistance they took and its subtype if applicable, the subject's name (if they are applying for another person), and the date they received the assistance
- The user who handles an assistance type could edit and delete the record, and it will be approved by the admin
- Total transactions and last date when a client received their assistance are also posted on the page
- The list of data can be filtered based on the client's/subject's name, barangay, gender, assistance type, assistance subtype, and date received


**d. Client Profiles**
- This page shows the personal details of the client and automatically changes once the details also change
- Shows the overview of the assistance received by the client and their dates
- For new transactions under the client, the user can click "_new transaction_" which copies the existing data of the client and can change if there are changes
- If the client is requesting medical/financial assistance, the user can fill out the AICS intake sheet and can be printed


**e. Entry Page**
- If the client is new, this page is where the user can input the transaction made by the client, and the system automatically makes a profile for the client
- '_Open form page_' directs the user to the AICS intake sheet


**f. Settings Page**
- Shows the name, email, role, and assigned assistance of the user
- Allows the user to change his/her password
- Shows the history of activities done by the user with timestamps


**g. Overall**
- The system has a standardized format for the entries to be submitted:

  i. _Names_
  - Format is 'Last Name, First Name Middle Initial.'
  - 'Last Name, First Name' is the format if the client does not have a middle initial 


  ii. _Age, Monthly Income, and Amount_
  - Only accepts numbers


  iii. _Phone Number_
  - '09XXXXXXXXX' where the entry should start with '09' and has 11 digits


  iv. _Dates_
  - Format is 'YYYY-MM-DD'


  v. _Time_
  - Format is 'HH:MM' that only accepts military time
