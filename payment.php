<?php
require "includes/db.php";
require "includes/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $is_active = $user["is_active"] === "1" ? "approved" : "unapproved";
    $subscription_status = $user["subscription_status"];
    $created_at = $user["created_at"];
}

// Calculate trial period
$now = new DateTime();
$dateCreated = new DateTime($created_at);
$trialEndDate = (clone $dateCreated)->modify('+' . TRIAL_PERIOD . ' days');
$diff = $now->diff($trialEndDate);
$daysRemaining = $diff->days;
$isTrialActive = ($now < $trialEndDate);

// Paystack configuration
define('PAYSTACK_PUBLIC_KEY', 'pk_test_a0b1602c0ac0e222294eae275244a14d6c61c106');
define('PAYSTACK_SECRET_KEY', 'sk_test_5f1720558a802e25f9c4c26d844f69dd6dbd1c1c');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Upgrade Your Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #2d2d2d;
            color: white;
            margin: 0;
            padding: 20px;
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .trial-status {
            max-width: 600px;
            margin: 0 auto 40px auto;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .active-trial {
            background: #e0ffe5;
            color: #2ecc71;
            border: 2px solid #2ecc71;
        }

        .expired-trial {
            background: #ffe0e0;
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }

        .pricing-container {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pricing-card {
            background: white;
            color: black;
            border-radius: 12px;
            width: 300px;
            padding: 30px 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
        }

        .gradient-basic {
            background: linear-gradient(135deg, #8e44ad, #2980b9);
            color: white;
        }

        .gradient-pro {
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            color: white;
        }

        .gradient-business {
            background: linear-gradient(135deg, #2ecc71, #16a085);
            color: white;
        }

        .pricing-card h3 {
            margin-top: 10px;
        }

        .pricing-card ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .pricing-card ul li {
            margin: 10px 0;
            font-size: 15px;
        }

        .pricing-card button {
            border: none;
            padding: 12px 25px;
            font-size: 15px;
            font-weight: bold;
            color: white;
            border-radius: 30px;
            margin-top: 15px;
        }

        .btn-basic {
            background-color: #2980b9;
        }

        .btn-pro {
            background-color: #e67e22;
        }

        .btn-business {
            background-color: #27ae60;
        }

        .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <h1>Upgrade Your Account</h1>

    <div class="trial-status <?= $isTrialActive ? 'active-trial' : 'expired-trial' ?>">
        <?php if ($isTrialActive): ?>
            <h4>Your Trial is Active</h4>
            <p><strong><?= $daysRemaining ?> days</strong> remaining</p>
        <?php else: ?>
            <h4>Trial Ended</h4>
            <p>Ended <strong><?= $daysRemaining ?> days</strong> ago</p>
        <?php endif; ?>
        <a name="" id="" class="btn btn-danger" href="logout.php" role="button"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a>
    </div>

    <h2>Choose Your Plan</h2>

    <div class="pricing-container">
        <div class="pricing-card gradient-basic">
            <div class="icon"><i class="fas fa-folder-open"></i></div>
            <h3>Basic</h3>
            <p><strong>KES 1,299/mo</strong></p>
            <ul>
                <li>Unlimited access</li>
                <li>Essential support</li>
                <li>Good for starters</li>
            </ul>
            <button class="btn-basic" onclick="payWithPaystack(this,1299, 'basic')">Subscribe</button>
        </div>

        <div class="pricing-card gradient-pro">
            <div class="icon"><i class="fas fa-cogs"></i></div>
            <h3>Pro</h3>
            <p><strong>KES 2,599/mo</strong></p>
            <ul>
                <li>Advanced features</li>
                <li>Faster support</li>
                <li>Better performance</li>
            </ul>
            <button class="btn-pro" onclick="payWithPaystack(this,2599, 'pro')">Subscribe</button>
        </div>

        <div class="pricing-card gradient-business">
            <div class="icon"><i class="fas fa-briefcase"></i></div>
            <h3>Business</h3>
            <p><strong>KES 3,999/mo</strong></p>
            <ul>
                <li>All Pro features</li>
                <li>Priority support</li>
                <li>Custom integrations</li>
            </ul>
            <button class="btn-business" onclick="payWithPaystack(this,3999, 'business')">Subscribe</button>
        </div>
    </div>

    <script>
function payWithPaystack(button, amount, plan) {
    const email = "<?php echo $_SESSION['email'] ?? 'customer@example.com'; ?>";
    const userId = "<?php echo $_SESSION['user_id'] ?? ''; ?>";
    const fullName = "<?php echo isset($_SESSION['full_name']) ? addslashes($_SESSION['full_name']) : ''; ?>";

    const ref = 'PS-' + Math.floor(Math.random() * 1000000000 + 1) + '-' + Date.now();

    let handler = PaystackPop.setup({
        key: '<?php echo PAYSTACK_PUBLIC_KEY; ?>',
        email: email,
        amount: amount * 100,
        currency: 'KES',
        ref: ref,
        metadata: {
            custom_fields: [{
                display_name: "User ID",
                variable_name: "user_id",
                value: userId
            }],
            customer_name: fullName,
            plan: plan
        },
        callback: function(response) {
            console.log('Paystack callback:', response); // for debug
            verifyPayment(button, response.reference, plan, amount);
        },
        onClose: function() {
            console.log('Payment window closed');
        }
    });

    handler.openIframe();
}

function verifyPayment(button, reference, plan, amount) {
    const originalButtonText = button.innerText;
    button.innerText = 'Verifying...';
    button.disabled = true;

    fetch('verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reference: reference,
            plan: plan,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Verification response:', data);
        if (data.status) {
            alert('Payment successful! Redirecting...');
            window.location.href = './student/dashboard.php?payment=success';
        } else {
            alert('Payment verification failed: ' + (data.message || 'Unknown error'));
            button.innerText = originalButtonText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during verification.');
        button.innerText = originalButtonText;
        button.disabled = false;
    });
}

    </script>
</body>

</html>