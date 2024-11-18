<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Holidays Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h1 {
            margin-bottom: 50px;
        }
        .container {
            display: flex;
            gap: 30px;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            text-align: center;
            font-size: 16px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #0056b3;
        }
        /* Make the image bigger */
        .logo {
            display: block;
            margin-bottom: 60px;
            width: 600px; /* Increase the width */
            height: auto; /* Maintain aspect ratio */
        }
    </style>
</head>
<body>

    <!-- Centered image above heading -->
    <img src="/Images/BankHolidays.jpg" alt="AVA Bank Holidays Generator" class="logo">
    
    <h1>AVA Bank Holidays Management</h1>
    
    <div class="container">
        <!-- Button to define standard set of bank holidays -->
        <form action="/bankholidays/select_client.php" method="get">
            <button class="button" type="submit">Define Standard Set of Bank Holidays for a Client</button>
        </form>

        <!-- Button to create a list of bank holidays -->
        <form action="/bankholidays/bankholidays.php" method="get">
            <button class="button" type="submit">Generate List of Bank Holidays</button>
        </form>
    </div>

</body>
</html>
