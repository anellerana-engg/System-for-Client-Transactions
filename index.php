<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Portal</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            background: #f1f4f8;
        }

        .header {
            background: #0b3d91;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 4px solid #d4a017;
        }

        .header img {
            width: 55px;
            height: 55px;

            filter: 
                drop-shadow(0 0 2px white)
                drop-shadow(0 0 3px white)
                drop-shadow(0 0 4px white);
        }

        .header-text {
            line-height: 1.2;
        }

        .header-text h2 {
            font-size: 16px;
            font-weight: 600;
        }

        .header-text p {
            font-size: 12px;
            opacity: 0.9;
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 60px);
        }

        .container {
            background: #ffffff;
            width: 380px;
            padding: 30px;
            border-radius: 6px;
            border-top: 6px solid #0b3d91;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-title {
            text-align: center;
            margin-bottom: 20px;
            color: #0b3d91;
            font-weight: 500;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            top: 12px;
            left: 10px;
            color: #0b3d91;
        }

        .input-group input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ccc;
            border-radius: 4px;
            outline: none;
        }

        .input-group input:focus {
            border-color: #0b3d91;
        }

        .input-group label {
            position: absolute;
            top: 10px;
            left: 35px;
            font-size: 13px;
            color: #888;
            transition: 0.3s;
            pointer-events: none;
        }

        .input-group input:focus ~ label,
        .input-group input:not(:placeholder-shown) ~ label {
            top: -8px;
            left: 30px;
            background: white;
            padding: 0 5px;
            font-size: 11px;
            color: #0b3d91;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: #0b3d91;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn:hover {
            background: #082c6c;
        }

        .links {
            text-align: center;
            margin-top: 15px;
        }

        .links p {
            font-size: 13px;
            color: #555;
        }

        .links button {
            background: none;
            border: none;
            color: #d4a017;
            font-weight: bold;
            cursor: pointer;
        }

        .links button:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="header">
        <img src="logo.png" alt="Logo">

        <div class="header-text">
            <h2>Office</h2>
            <p>System</p>
        </div>
    </div>


    <div class="main">

        <div class="container" id="signup" style="display:none;">
          <h1 class="form-title">Register</h1>
          <form method="post" action="register.php">

            <div class="input-group">
               <i class="fas fa-user"></i>
               <input type="text" name="fName" placeholder=" " required>
               <label>First Name</label>
            </div>

            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" placeholder=" " required>
                <label>Last Name</label>
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder=" " required>
                <label>Email</label>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder=" " required>
                <label>Password</label>
            </div>

            <div class="input-group">
            <i class="fas fa-user-shield"></i>
            <select name="role" id="roleSelect" required style="width:100%; padding:10px 10px 10px 35px; border:1px solid #ccc; border-radius:4px;">
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
                <option value="intern">Intern</option>
            </select>
            </div>

            <div class="input-group">
            <i class="fas fa-hand-holding-heart"></i>
            <select name="assistance" id="assistanceSelect" style="width:100%; padding:10px 10px 10px 35px; border:1px solid #ccc; border-radius:4px;">
                <option value="" disabled selected>Select Assistance</option>
                <option>AICS</option>
                <option>Balik Probinsya</option>
                <option>Burial</option>
                <option>Cash for Work</option>
                <option>ESA</option>
                <option>Food and Non-Food Items</option>
                <option>Indigency (Court)</option>
                <option>Indigent (PhilHealth)</option>
                <option>Pag-Abot Program</option>
                <option>PMC</option>
                <option>PWD</option>
                <option>Referral (Medical)</option>
                <option>Solo Parent</option>
                <option>Women's Kalipi</option>
            </select>
        </div>

           <input type="submit" class="btn" value="Sign Up" name="signUp">
          </form>

          <div class="links">
            <p>Already Have Account?</p>
            <button id="signInButton">Sign In</button>
          </div>
        </div>

        <div class="container" id="signIn">
            <h1 class="form-title">Sign In</h1>
            <form method="post" action="register.php">

              <div class="input-group">
                  <i class="fas fa-envelope"></i>
                  <input type="email" name="email" placeholder=" " required>
                  <label>Email</label>
              </div>

              <div class="input-group">
                  <i class="fas fa-lock"></i>
                  <input type="password" name="password" placeholder=" " required>
                  <label>Password</label>
              </div>

             <input type="submit" class="btn" value="Sign In" name="signIn">
            </form>

            <p style="text-align:right; font-size:12px;">
            <a href="forgot_password.php">Forgot Password?</a>
            </p>

            <div class="links">
              <p>Don't have account yet?</p>
              <button id="signUpButton">Sign Up</button>
            </div>
        </div>

    </div>

    <script src="script.js"></script>
    <script>
function showAlert(message){
    const box = document.createElement("div");

    box.style.position = "fixed";
    box.style.top = "50%";
    box.style.left = "50%";
    box.style.transform = "translate(-50%, -50%)";
    box.style.background = "white";
    box.style.padding = "20px";
    box.style.borderRadius = "10px";
    box.style.boxShadow = "0 5px 20px rgba(0,0,0,0.2)";
    box.style.zIndex = "9999";
    box.style.textAlign = "center";

    box.innerHTML = message + "<br><br><button onclick='this.parentElement.remove()'>OK</button>";

    document.body.appendChild(box);
}


function checkConnection(){
    if(!navigator.onLine){
        document.getElementById("pinField").style.display = "block";
    }
}

window.addEventListener("load", checkConnection);
window.addEventListener("offline", checkConnection);

document.getElementById("roleSelect").addEventListener("change", function() {
    const role = this.value;
    const assistance = document.getElementById("assistanceSelect");

    if(role === "admin" || role === "intern"){
        assistance.removeAttribute("required");
        assistance.value = "";
    } else {
        assistance.setAttribute("required", "required");
    }
});
</script>
</body>
</html>