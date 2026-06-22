<?php
include("adminSession.php");
include("controller.php");
include("db.php"); 


$vendors = get_vendorList();
$admin = getAdminList();

$applications = [];
$appsQuery = mysqli_query($link, "SELECT * FROM vendor_applications ORDER BY AppliedOn DESC");
if($appsQuery) {
    while($row = mysqli_fetch_assoc($appsQuery)) {
        $applications[] = $row;
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'update_app' && isset($_GET['id'])) {
    $appId = intval($_GET['id']);
    $status = mysqli_real_escape_string($link, $_GET['status']);
    mysqli_query($link, "UPDATE vendor_applications SET Status='$status' WHERE AppID=$appId");
    header("Location: adminpage.php#apps");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BiteGo | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; color: #000; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #fff; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; padding: 30px 0; box-shadow: 2px 0 15px rgba(0,0,0,0.02); z-index: 10; }
        .sidebar-brand { font-size: 28px; font-weight: 900; text-align: center; letter-spacing: 2px; margin-bottom: 5px; }
        .sidebar-subtitle { font-size: 13px; font-weight: 700; text-align: center; color: #888; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 50px; }
        .nav-item { padding: 15px 40px; font-size: 16px; font-weight: 600; color: #666; text-decoration: none; transition: all 0.3s; border-left: 4px solid transparent; display: flex; align-items: center; cursor: pointer; }
        .nav-item:hover { color: #000; background-color: #fafafa; }
        .nav-item.active { color: #000; border-left-color: #000; background-color: #f8f9fa; }
        .logout-btn { margin-top: auto; color: #d9534f; }
        .logout-btn:hover { background-color: #fdf0f0; color: #c9302c; }
        
        .main-content { flex: 1; padding: 40px 60px; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .top-header h1 { margin: 0; font-size: 32px; font-weight: 800; }
        .admin-profile { font-weight: bold; font-size: 14px; background: #fff; padding: 10px 20px; border-radius: 30px; border: 1px solid #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }

        .view-section { display: none; animation: fadeIn 0.4s ease forwards; }
        .view-section.active-view { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px; }
        .stat-card { background-color: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eee; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .stat-title { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 10px; }
        .stat-number { font-size: 42px; font-weight: 900; color: #000; }

        .table-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header-row h2 { margin: 0; font-size: 22px; }
        .btn-add { background-color: #000; color: #fff; padding: 12px 25px; border-radius: 8px; font-size: 14px; font-weight: bold; border: none; cursor: pointer; transition: all 0.3s; }
        .btn-add:hover { background-color: #444; transform: scale(1.02); }
        
        .table-container { background-color: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eee; overflow: hidden; margin-bottom:40px;}
        table { width: 100%; border-collapse: collapse; text-align: left; }
        thead { background-color: #fafafa; border-bottom: 2px solid #eee; }
        th { padding: 18px 25px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #888; }
        td { padding: 18px 25px; font-size: 15px; font-weight: 500; border-bottom: 1px solid #eee; }
        tbody tr:hover td { background-color: #fdfdfd; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .badge-active { background-color: #e8f5e9; color: #2e7d32; }
        .badge-pending { background-color: #fff4e5; color: #f57f17; }
        .badge-rejected { background-color: #fdf0f0; color: #d9534f; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .status-open { background-color: #e8f5e9; color: #2e7d32; }
        .status-closed { background-color: #fdf0f0; color: #d9534f; }

        .action-link { color: #000; font-size: 13px; font-weight: bold; text-decoration: none; margin-right: 15px; cursor: pointer; }
        .action-link:hover { text-decoration: underline; }
        .action-delete { color: #d9534f; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 450px; max-height: 90vh; overflow-y: auto;}
        .modal-content h2 { margin-top: 0; font-size: 24px; margin-bottom: 20px;}
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .modal-buttons { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;}
        .modal-btn { padding: 12px 25px; font-size: 14px; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: none; }
        .btn-cancel { background-color: #eee; color: #000; }
        .btn-cancel:hover { background-color: #ddd; }
        
        .btn-read { background: #1976d2; color: #fff; padding: 8px 15px; border-radius: 6px; font-size: 12px; font-weight: bold; text-transform: uppercase; cursor: pointer; border: none;}
        .btn-read:hover { background: #115293; }

.toast {
    visibility: hidden;
    min-width: 300px;
    background-color: #28a745;
    color: white;
    text-align: center;
    border-radius: 8px;
    padding: 15px 20px;
    position: fixed;
    top: 20px;
    left: 50%;
    z-index: 9999;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);

    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
    transition: all 0.5s ease;
}

.toast.show {
    visibility: visible;
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

    .toast.success {
    background-color: #28a745;
    }

    .toast.error {
    background-color: #dc3545;
}

    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">BiteGo.</div>
        <div class="sidebar-subtitle">Admin Portal</div>
        <a href="#dashboard" class="nav-item active" onclick="switchView(event, 'dashboard', this)"><i class="fa-solid fa-chart-pie" style="width:25px;"></i> Dashboard</a>
        <a href="#manage" class="nav-item" onclick="switchView(event, 'manage', this)"><i class="fa-solid fa-store" style="width:25px;"></i> Manage Vendors</a>
        <a href="#apps" class="nav-item" onclick="switchView(event, 'apps', this)"><i class="fa-solid fa-envelope-open-text" style="width:25px;"></i> Vendor Applications</a>
        <a href="#Admin" class="nav-item" onclick="switchView(event, 'Admin', this)"><i class="fa-solid fa-envelope-open-text" style="width:25px;"></i> Admin</a>
        <a href="logout.php" class="nav-item logout-btn"><i class="fa-solid fa-right-from-bracket" style="width:25px;"></i> Log Out</a>
    </aside>

    <main class="main-content">
        <div class="top-header">
            <h1 id="pageTitle">Overview Dashboard</h1>
            <div class="admin-profile">Welcome, <?php echo $_SESSION['UserName']; ?>.</div>
        </div>

        <div id="dashboard" class="view-section active-view">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Registered Vendors</div>
                    <div class="stat-number"><?php echo getTotalVendors(); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">New Applications</div>
                    <div class="stat-number"><?php echo count($applications); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">System Uptime</div>
                    <div class="stat-number">100%</div>
                </div>
            </div>

            <div class="table-header-row"><h2>Active Platform Accounts</h2></div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Vendor ID</th><th>Restaurant Name/Email</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach($vendors as $v): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['UserID']); ?></td>
                                <td>
                                 <strong><?php echo htmlspecialchars($v['UserName']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($v['UserEmail']); ?></small>
                                </td>
                                     <td>
                                        <span class="status-badge <?php echo $v['StoreStatus'] == 'Open' ? 'status-open' : 'status-closed'; ?>">
                                            <?php echo $v['StoreStatus'] == 'Open' ? 'Open' : 'Closed'; ?>
                                        </span>
                                    </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="manage" class="view-section">
            <div class="table-header-row">
                <h2>Vendor Control Panel</h2>
                <button class="btn-add" onclick="openAddModal()">+ Add New Vendor</button>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Vendor ID</th><th>Restaurant Name</th><th>Owner Email</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($vendors as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f['UserID']); ?></td>
                            <td><?php echo htmlspecialchars($f['UserName']); ?></td>
                            <td><?php echo htmlspecialchars($f['UserEmail']); ?></td>
                            <td>
                                <a class="action-link" onclick="openEditModal('<?php echo $f['UserID']; ?>', '<?php echo addslashes(htmlspecialchars($f['UserName'])); ?>', '<?php echo addslashes(htmlspecialchars($f['UserEmail'])); ?>')">Edit Credentials</a>
                                <a class="action-link action-delete" onclick="confirmDelete('<?php echo $f['UserID']; ?>')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="Admin" class="view-section">
            <div class="table-header-row">
                <h2>Admin Control Panel</h2>
                <button class="btn-add" onclick="adminAddModal()">+ Add New Admin</button>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Admin ID</th><th>Admin Name</th><th>Admin Email</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($admin as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['UserID']);?></td>
                            <td><?php echo htmlspecialchars($a['UserName']);?></td>
                            <td><?php echo htmlspecialchars($a['UserEmail']);?></td>
                            <td>
                                <a class="action-link" onclick="editAdmin('<?php echo $a['UserID']; ?>', '<?php echo addslashes(htmlspecialchars($a['UserName'])); ?>', '<?php echo addslashes(htmlspecialchars($a['UserEmail'])); ?>')">Edit Credentials</a>
                                <a class="action-link action-delete" onclick="adminDelete('<?php echo $a['UserID']; ?>')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="apps" class="view-section">
            <div class="table-header-row"><h2>Vendor Applications</h2></div>
            <div class="table-container">
                <table>
                    <thead><tr><th>Date Applied</th><th>Email</th><th>Req. Met</th><th>Status</th><th>Review</th></tr></thead>
                    <tbody>
                        <?php if(empty($applications)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px;">No pending applications.</td></tr>
                        <?php else: ?>
                            <?php foreach($applications as $app): ?>
                                <tr>
                                    <td><?php echo date("d M Y", strtotime($app['AppliedOn'])); ?></td>
                                    <td><?php echo htmlspecialchars($app['Email']); ?></td>
                                    <td><?php echo $app['RequirementsMet'] ? '<i class="fa-solid fa-check" style="color:green;"></i> Yes' : '<i class="fa-solid fa-x" style="color:red;"></i> No'; ?></td>
                                    <td>
                                        <?php if($app['Status'] == 'Pending') echo '<span class="badge badge-pending">Pending</span>'; ?>
                                        <?php if($app['Status'] == 'Approved') echo '<span class="badge badge-active">Approved</span>'; ?>
                                        <?php if($app['Status'] == 'Rejected') echo '<span class="badge badge-rejected">Rejected</span>'; ?>
                                    </td>
                                    <td>
                                        <button class="btn-read" onclick="openAppModal(<?php echo $app['AppID']; ?>, '<?php echo addslashes(htmlspecialchars($app['Email'])); ?>', '<?php echo addslashes(htmlspecialchars($app['Description'])); ?>', '<?php echo $app['ProposedDate']; ?>', '<?php echo $app['Status']; ?>')">
                                            <i class="fa-solid fa-file-lines"></i> Read Full
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="addVendorModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Register New Vendor</h2>
            <form method="POST" action="process_add_vendor.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Restaurant Name</label>
                    <input type="text" name="RestaurantName" required>
                </div>
                <div class="form-group">
                    <label>Vendor Email</label>
                    <input type="email" name="VendorEmail" required>
                </div>
                <div class="form-group">
                    <label>Vendor Password</label>
                    <input type="password" name="VendorPassword" required>
                </div>
                <div class="form-group">
                    <label>Vendor Store Image</label>
                    <input type="file" name="vendorImage" accept="image/*">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="modal-btn" style="background:#000; color:#fff;">Add Vendor</button>
                </div>
            </form>
        </div>
    </div>


    <div id="addAdminModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Register New Admin</h2>
            <form method="POST" action="addAdmin.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Admin Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                 <label>Admin Email</label>
                    <input type="email" name="email" required>

                    <?php if(isset($_GET['error']) && $_GET['error'] == 'email_exists'){ ?>
                    <div style="color:#d9534f; font-size:13px; margin-top:5px;">
                         An account with this email address already exists.
                    </div>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="pass" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeAddAdminModal()">Cancel</button>
                    <button type="submit" class="modal-btn" style="background:#000; color:#fff;">Add Admin</button>
                </div>
            </form>
        </div>
    </div>


    <div id="editVendorModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Edit Vendor Credentials</h2>
            <form method="POST" form id="editVendorForm" action="process_edit_vendor.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_userid" id="editUserID">
                <div class="form-group">
                    <label>Restaurant Name</label>
                    <input type="text" name="edit_name" id="editName" required>
                </div>
                <div class="form-group">
                    <label>Vendor Email</label>
                    <input type="email" name="edit_email" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label>New Password <span style="font-weight:normal; color:#888;">(Leave blank to keep current)</span></label>
                    <input type="password" name="edit_password" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label>Update Store Image <span style="font-weight:normal; color:#888;">(Leave blank to keep current)</span></label>
                    <input type="file" name="edit_vendorImage" accept="image/*">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="modal-btn" style="background:#000; color:#fff;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editAdminModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Edit Admin Credentials</h2>
            <form method="POST" action="process_edit_admin.php" enctype="multipart/form-data">
                <input type="hidden" name="editAdminID" id="AdminID">
                <div class="form-group">
                    <label>Admin Name</label>
                    <input type="text" name="editAdminName" id="AdminName" required>
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="editAdminEmail" id="AdminEmail" required>
                </div>
                <div class="form-group">
                    <label>New Password <span style="font-weight:normal; color:#888;">(Leave blank to keep current)</span></label>
                    <input type="password" name="editPass" placeholder="Enter new password">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeAdminModal()">Cancel</button>
                    <button type="submit" class="modal-btn" style="background:#000; color:#fff;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="appReviewModal" class="modal-overlay">
        <div class="modal-content" style="width: 500px;">
            <h2>Review Application</h2>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; margin-bottom: 20px;">
                <p style="margin: 0 0 10px 0;"><strong>Applicant:</strong> <span id="modalAppEmail"></span></p>
                <p style="margin: 0 0 10px 0;"><strong>Proposed Launch:</strong> <span id="modalAppDate"></span></p>
                <p style="margin: 0 0 5px 0;"><strong>Business Description:</strong></p>
                <p id="modalAppDesc" style="font-size: 14px; color: #555; line-height: 1.5; margin: 0; padding: 10px; background: #fff; border: 1px dashed #ccc; border-radius: 6px;"></p>
            </div>

            <div id="signatureSection">
                <p style="font-size: 13px; font-weight: bold; color: #d9534f; margin-bottom: 10px;"><i class="fa-solid fa-pen-nib"></i> Admin Signature Required</p>
                <div class="signature-box">
                    <canvas id="signaturePad" width="400" height="150"></canvas>
                    <br>
                    <button type="button" class="clear-sig-btn" onclick="clearSignature()"><i class="fa-solid fa-rotate-left"></i> Clear Signature</button>
                </div>
                
                <div class="modal-buttons" style="margin-top: 15px;">
                    <button type="button" class="modal-btn btn-cancel" onclick="closeAppModal()">Cancel</button>
                    <button type="button" class="modal-btn" style="background:#d9534f; color:#fff;" onclick="submitAppReview('Rejected')"><i class="fa-solid fa-x"></i> Reject</button>
                    <button type="button" class="modal-btn" style="background:#2e7d32; color:#fff;" onclick="submitAppReview('Approved')"><i class="fa-solid fa-check"></i> Approve</button>
                </div>
            </div>
            
            <div id="processedMsg" style="display: none; text-align: center; color: #888; font-weight: bold; padding: 20px;">
                This application has already been processed.
                <br><br>
                <button type="button" class="modal-btn btn-cancel" onclick="closeAppModal()">Close</button>
            </div>

        </div>
    </div>

    <script>

    function showToast(message, type = "success") {

    const toast = document.getElementById("toast");

    toast.textContent = message;

    toast.className = "toast";
    toast.classList.add(type);
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 3000);
}
   window.onload = function() {

    if(window.location.hash) {
        let target = document.querySelector(`a[href="${window.location.hash}"]`);
        if(target) target.click();
    }

    const params = new URLSearchParams(window.location.search);

    if(params.get('showModal') === 'addAdmin'){
        document.getElementById('addAdminModal').style.display = 'flex';
    }

   <?php if(isset($_GET['success']) && $_GET['success'] == 'admin_deleted') { ?>
        showToast("Admin account deleted successfully.", "success");
    <?php } ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'delete_failed') { ?>
        showToast("Failed to delete account.", "error");
    <?php } ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'email_exists') { ?>
        showToast("This email address is already registered.", "error");
    <?php } ?>

    <?php if(isset($_GET['success']) && $_GET['success'] == 'email_registered') { ?>
        showToast("Admin account created successfully.", "success");
    <?php } ?>

    <?php if(isset($_GET['success']) && $_GET['success'] == 'vendor_registered') { ?>
        showToast("Vendor account created successfully.", "success");
    <?php } ?>

    <?php if(isset($_GET['success']) && $_GET['success'] == 'credential_updated') { ?>
        showToast("account credentials updated succesfully.", "success");
    <?php } ?>
        <?php if(isset($_GET['error']) && $_GET['error'] == 'update_failed') { ?>
        showToast("Admin credential update failed.", "error");
    <?php } ?>

       <?php if(isset($_GET['success']) && $_GET['success'] == 'vendor_deleted') { ?>
        showToast("vendor account deleted successfully.", "success");
    <?php } ?>
    
        <?php if(isset($_GET['success']) && $_GET['success'] == 'credential_updatedNoPic') { ?>
        showToast("account credentials updated succesfully (NO PICTURE UPLOADED).", "success");
    <?php } ?>

        <?php if(isset($_GET['error']) && $_GET['error'] == 'credential_updatedPicError') { ?>
        showToast("account credentials updated succesfully (PICTURE FAILED TO UPLOADED).", "error");
    <?php } ?>

};

        function switchView(event, viewId, element) {
            if(event) event.preventDefault(); 
            document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active-view'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

            document.getElementById(viewId).classList.add('active-view');
            element.classList.add('active');
            document.getElementById('pageTitle').innerText = element.innerText.trim();
            window.history.replaceState(null, null, '#' + viewId);
        }
        function editAdmin(UserID,UserName,UserEmail){
                       
            document.getElementById('AdminID').value = UserID;
            document.getElementById('AdminName').value = UserName;
            document.getElementById('AdminEmail').value = UserEmail;
            document.getElementById('editAdminModal').style.display = 'flex';
        }
        function closeAdminModal() { document.getElementById('editAdminModal').style.display = 'none'; }
function adminDelete(UserID) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete this admin account? This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // Crimson red for delete
        cancelButtonColor: '#3085d6', // Clear blue for cancel
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "deleteAdmin.php?UserID=" + UserID;
        }
    });
}

        function adminAddModal(){
            document.getElementById('addAdminModal').style.display = 'flex'; 

        }
        function closeAddAdminModal() { document.getElementById('addAdminModal',).style.display = 'none'; }



        function openAddModal() { document.getElementById('addVendorModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('addVendorModal',).style.display = 'none'; }

        function openEditModal(id, name, email) {
            document.getElementById('editUserID').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editVendorModal').style.display = 'flex';
        }
        function closeEditModal() { document.getElementById('editVendorModal').style.display = 'none'; }

        function confirmDelete(UserID) {
        Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete this vendor account? This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // Crimson red for delete
        cancelButtonColor: '#3085d6', // Clear blue for cancel
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (result.isConfirmed) {
            window.location.href =  "deleteVendor.php?UserID=" + UserID;
        }
    });
}


        // SIGNATURE PAD LOGIC
        let currentAppId = 0;
        let hasSigned = false;
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');

        function openAppModal(id, email, desc, date, status) {
            currentAppId = id;
            document.getElementById('modalAppEmail').innerText = email;
            document.getElementById('modalAppDesc').innerText = desc;
            document.getElementById('modalAppDate').innerText = date;
            
            if (status !== 'Pending') {
                document.getElementById('signatureSection').style.display = 'none';
                document.getElementById('processedMsg').style.display = 'block';
            } else {
                document.getElementById('signatureSection').style.display = 'block';
                document.getElementById('processedMsg').style.display = 'none';
                clearSignature();
            }
            document.getElementById('appReviewModal').style.display = 'flex';
        }

        function closeAppModal() { document.getElementById('appReviewModal').style.display = 'none'; }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.beginPath();
            hasSigned = false;
        }

        function submitAppReview(newStatus) {
            if(!hasSigned) {
                alert("Please draw your signature to authorize this decision.");
                return;
            }
            window.location.href = `?action=update_app&id=${currentAppId}&status=${newStatus}`;
        }

        let drawing = false;
        canvas.addEventListener('mousedown', (e) => {
            drawing = true; hasSigned = true;
            ctx.beginPath();
            ctx.moveTo(e.offsetX, e.offsetY);
        });
        canvas.addEventListener('mousemove', (e) => {
            if(drawing) { ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); }
        });
        canvas.addEventListener('mouseup', () => { drawing = false; ctx.closePath(); });
        canvas.addEventListener('mouseout', () => { drawing = false; ctx.closePath(); });

        canvas.addEventListener('touchstart', (e) => {
            e.preventDefault(); drawing = true; hasSigned = true;
            let touch = e.touches[0];
            let rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
        });
        canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            if(drawing) {
                let touch = e.touches[0];
                let rect = canvas.getBoundingClientRect();
                ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
                ctx.stroke();
            }
        });
        canvas.addEventListener('touchend', () => { drawing = false; ctx.closePath(); });
    </script>
    <div id="toast" class="toast"></div>
</body>
</html>