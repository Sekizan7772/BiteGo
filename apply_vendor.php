<!DOCTYPE html>
<html>
<head>
    <title>Apply for Vendor | BiteGo</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Alexandria', sans-serif; background-color: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px 20px;}
        .apply-card { background: #fff; width: 100%; max-width: 550px; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .apply-header { text-align: center; margin-bottom: 30px; }
        .apply-header h2 { font-size: 32px; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px; }
        .apply-header p { color: #666; font-size: 15px; margin: 0; line-height: 1.5; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 14px; border: 1px solid #ccc; border-radius: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; transition: 0.3s; }
        .form-group input:focus, .form-group textarea:focus { border-color: #000; outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        .checkbox-group { background: #fafafa; border: 1px solid #eee; padding: 15px; border-radius: 10px; margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-start; }
        .checkbox-group input { width: 20px; height: 20px; margin-top: 2px; cursor: pointer; }
        .checkbox-group label { font-size: 13px; font-weight: 500; color: #555; text-transform: none; line-height: 1.5; cursor: pointer; margin: 0;}
        
        .btn-submit { width: 100%; background: #000; color: #fff; border: none; padding: 16px; border-radius: 10px; font-weight: 800; font-size: 16px; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #333; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .back-link { display: block; text-align: center; margin-top: 20px; font-weight: 700; color: #888; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #000; }
    </style>
</head>
<body>

    <div class="apply-card">
        <div class="apply-header">
            <h2>Partner with BiteGo</h2>
            <p>Fill out the form below and our team will review your restaurant for approval into our system.</p>
        </div>

        <form action="process_apply_vendor.php" method="POST">
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="email" placeholder="restaurant@email.com" required>
            </div>
            
            <div class="form-group">
                <label>Describe Your Restaurant</label>
                <textarea name="description" rows="4" placeholder="What kind of food do you serve? Where are you located?" required></textarea>
            </div>

            <div class="form-group">
                <label>Proposed Launch Date</label>
                <input type="date" name="proposed_date" required>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="req" name="requirements" required>
                <label for="req"><strong>I confirm that I meet the requirements:</strong> I have a valid business license, a certified food safety handler's permit, and I agree to BiteGo's vendor terms of service.</label>
            </div>

            <button type="submit" class="btn-submit">Send Application</button>
        </form>

        <a href="frontpage.php" class="back-link">&larr; Cancel and return Home</a>
    </div>

</body>
</html>