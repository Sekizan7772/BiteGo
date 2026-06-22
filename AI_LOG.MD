# AI Usage & Disclosure Log

**Project Component:** Database Schema & Relationship Mapping (Phase 2)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I needed to design a normalized, relational database structure for the BiteGO checkout system (`orders` and `order_items` tables) in MySQL to ensure that a single order could handle multiple food items cleanly without data duplication or redundancy.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was: 
*"I am building a food ordering system called BiteGO using PHP and MySQL. I need to design the database tables for the order checkout system. Show me how to structure the tables so a user can buy multiple food items in one order."*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
Yes, the AI provided a functional database schema utilizing three tables: `users`, `orders`, and `order_items` linked via Foreign Keys. I verified the layout by running the generated `CREATE TABLE` queries directly in phpMyAdmin to ensure the tables were successfully constructed with their relational integrity constraints intact.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
The AI hardcoded the `item_name` and `price` directly into the transactional `order_items` table without linking it back to a central inventory. I refined this by creating an independent `menu_items` master table. I then modified `order_items` to reference `menu_item_id` as a foreign key while keeping an explicit `ordered_price` field to capture a permanent snapshot of the price at the exact second of purchase, preventing historical invoice records from changing if a vendor updates a food item's price in the future.

---

# AI Usage & Disclosure Log

**Project Component:** Responsive UI Grid & Sliding Cart Overlay (Phase 3)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I wanted to build a modern, responsive card-grid layout to display available food items seamlessly across mobile, tablet, and desktop viewports, accompanied by a slide-out shopping cart sidebar drawer that toggles from the main navigation header.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was: 
*"Give me the CSS and HTML for a responsive food delivery menu grid. Also, write a CSS-only slide-out shopping cart sidebar that appears when a user clicks the cart icon."*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
The AI provided working CSS Grid properties (`grid-template-columns: repeat(auto-fit, minmax(240px, 1fr))`) for the interface. For the sliding drawer, it utilized a CSS-only checkbox hack with the `:checked` pseudo-class selector. I tested this layout inside the browser by dragging the window to simulate mobile resizing.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
While the CSS Grid rules scaled perfectly, the CSS-only checkbox hack for the cart drawer drawer broke entirely when I attempted to load dynamic cart counts and data from my PHP session asynchronously. I stripped out the checkbox styling completely and rewrote the sidebar animation triggers using custom **JavaScript** class mutations (`cartDrawer.classList.toggle('active')`), enabling a dynamic, script-controlled interface state.

---

# AI Usage & Disclosure Log

**Project Component:** Asynchronous Session Cart Backend Processing (Phase 3)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I needed to implement dynamic cart quantity increments and decrements directly on the frontend UI without triggering a slow, full-page reload, then sync those quantities instantly back to the backend PHP server session variables.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was: 
*"How do I use JavaScript Fetch API to update food item quantities in a session-based PHP cart without refreshing the web page?"*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
Yes, the AI provided a JavaScript block using the browser's native `fetch()` API to POST JSON payloads to a separate backend file named `update_cart.php`. I verified the transmission using the Network tab in Chrome DevTools to inspect the JSON payloads being sent and received back.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
The AI's code implicitly trusted incoming numbers sent from the client browser. I realized that a malicious user could open their console and manually fetch a payload with a negative integer, reducing their checkout balance below zero. I refactored the backend script to strictly enforce server-side validation, casting incoming input values to absolute integers `> 0`. If a value falls below that threshold, the item is dropped safely via an explicit `unset()` routine to safeguard financial data integrity.

---

# AI Usage & Disclosure Log

**Project Component:** Checkout Form Penetration Testing & Vulnerability Fixes (Phase 4)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
During the system testing phase, I was auditing my checkout processing engine to verify that malicious strings typed into user input fields (like the delivery shipping address or custom kitchen notes) would not execute commands inside my MySQL database.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was: 
*"Here is my PHP code for saving an order to MySQL: [pasted standard raw SQL query concatenation block]. Is this safe from SQL injection? If not, how do I fix it?"*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
The AI pointed out that my current method used direct string interpolation (`'$address'`), making it highly vulnerable to SQL Injection attacks. It supplied fixed code refactored around parameterized prepared statements. I verified the security patch by running a script containing a dummy SQL attack payload string (`' OR '1'='1`) to confirm it was neutralised.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
I integrated the AI's structural suggestion into my existing checkout system script by implementing secure MySQLi prepared statement protocols (`$conn->prepare` and `$stmt->bind_param`). I mapped the variables carefully to match the explicit integer, double, and string types required by the BiteGO database tables, completely removing the raw query execution pattern.

---

# AI Usage & Disclosure Log

**Project Component:** User Authentication & Session Security (Phase 3)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I needed to implement a secure user registration and login system for BiteGO that ensures user passwords are not stored in plain text within the MySQL database and that user sessions persist safely across different pages.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was:  
*"How do I securely store user passwords during registration in PHP and verify them later during login?"*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
Yes, the AI provided a clean script demonstrating the use of PHP's native `password_hash()` function with the `PASSWORD_DEFAULT` algorithm for registration, and `password_verify()` combined with global `$_SESSION` handling for login authentication. I verified this by registering a test user account and inspecting the database through phpMyAdmin to confirm the password was converted into an unreadable 60-character cryptographic string.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
The AI's example template assumed a very basic user table structure. I adapted the hashing mechanism to bind directly with my parameterized query structure (`$stmt->bind_param`) to prevent any SQL injection vectors during authentication. I also refined the post-login session assignment by tracking unique customer metrics (`$_SESSION['user_id']` and `$_SESSION['username']`) to dynamically display customized greetings on the BiteGO dashboard.

---

# AI Usage & Disclosure Log

**Project Component:** Admin Menu Management & Image File Upload Validation (Phase 3)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I wanted to build an admin form that allows vendors to add new food items to the menu catalog, which required a secure backend script to handle uploading image files (like `.jpg` or `.png`) without introducing remote code execution vulnerabilities.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was:  
*"Write a PHP script to handle image file uploads for food items. Make sure it checks file size and file extensions so users can't upload malicious files."*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
The AI provided an processing script utilizing the `$_FILES` superglobal, including check boundaries for file sizes (limited to 2MB) and an array whitelist for allowed MIME types/extensions. I verified the script by trying to upload an invalid text file renamed as a `.jpg` image; the backend successfully caught the discrepancy and blocked the upload.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
The AI’s original script saved uploaded images using their original filename, which would cause older images to be overwritten if two files had the same name (e.g., `food.jpg`). I refined this by injecting a unique naming convention using PHP’s `uniqid()` function combined with `pathinfo()` to sanitize the filename before saving it to the `uploads/` directory. I then saved that unique path string into the `menu_items` database table matching the specific product row.

---

# AI Usage & Disclosure Log

**Project Component:** Client-Side Input Validation & Live UI Interactions (Phase 3)
**AI Tool Used:** Gemini

### 1. The Intent
*What specific problem were you trying to solve with AI?*
I needed to use JavaScript to validate user checkout form details (like checking if the phone number pattern is correct) right on the frontend before sending the data to PHP, saving server processing time and improving user experience.

### 2. The Interaction (Prompting)
*What was your primary prompt, and did you have to refine it?*
My primary prompt was:  
*"Give me a JavaScript event listener to prevent form submission if input fields are empty or if a phone number field doesn't match standard patterns."*

### 3. The Output & Verification
*Did the AI provide working code? How did you test/verify it?*
Yes, the AI provided a JavaScript block that hooks onto the HTML form's `submit` event, runs standard regex tests against the fields, and calls `event.preventDefault()` alongside visual error messages if verification fails. I verified it by attempting to click the checkout button with an incorrectly formatted phone number string; the interface instantly caught the error without reloading the browser window.

### 4. Human Refinement
*What changes did YOU make to the AI’s code to make it fit your specific project?*
The AI's example used browser default `alert()` pop-ups to display validation errors, which ruined the look of the web application. I removed the alerts and refactored the script to dynamically inject red text alerts directly under the respective form elements by mutating the DOM container's inner HTML (`errorDiv.innerText`), creating a highly cohesive interface matching the rest of the BiteGO theme.