<footer class="app-footer">Yeah Kenyan Academy @<?php echo date("Y"); ?></footer><!-- Modal -->
<div class="modal fade" id="editUserDetailsModal" tabindex="-1" role="dialog" data-bs-backdrop="static" aria-labelledby="modalTitleId" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitleId">Edit User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="editUserForm">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editFullName" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="student">Student</option>
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSubscriptionPlan" class="form-label">Subscription Plan</label>
                            <select class="form-select" id="editSubscriptionPlan" name="subscription_plan" required>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSubscriptionStatus" class="form-label">Subscription Status</label>
                            <select class="form-select" id="editSubscriptionStatus" name="subscription_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="editIsActive" name="is_active">
                            <label class="form-check-label" for="editIsActive">Active User</label>
                        </div>
                        <input type="hidden" id="editUserId" name="user_id">
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="saveUserDetails" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
    <!-- <script>
    
        (function(d, t) {
            var BASE_URL = "https://app.chatwoot.com";
            var g = d.createElement(t),
                s = d.getElementsByTagName(t)[0];
            g.src = BASE_URL + "/packs/js/sdk.js";
            g.async = true;
            s.parentNode.insertBefore(g, s);
            g.onload = function() {
                window.chatwootSDK.run({
                    websiteToken: 'TSR7CbTKcay6AjaxXpj7M5qP',
                    baseUrl: BASE_URL
                })
            }
        })(document, "script");
    </script> -->
</div>

<script src="../../assets/js/medium-zoom.min.js"></script>
<script type="module" src="../../chat.js"></script>
<!-- <script src="../../assets/js/chart.min.js"></script> -->
<script src="../../assets/js/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="<?php echo BASE_URL; ?>assets/js/dashboard.js"></script>
<!-- Bootstrap 5 Bundle with Popper -->
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/adminlte.min.js" crossorigin="anonymous"></script>
<script>
    mediumZoom('.zoomable');
    const textarea = document.querySelector('.chatInput');
    const username = "<?php echo ucfirst($_SESSION['role']) . " " . $_SESSION['full_name']; ?>"; // insert via PHP
    const receiver = "support"; // or get dynamically
    textarea ? textarea.addEventListener("input", () => {
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                type: "typing",
                from: username,
                to: receiver,
                message: textarea.value
            }));
        }
    }) : "";



    function showTypingIndicator(user, message) {
        const chatBody = document.querySelector('.chat-body');
        let existing = document.querySelector('.typingIndicator');
        existing.innerHTML = `${user} is typing...`;

        clearTimeout(window.typingTimeout);
        window.typingTimeout = setTimeout(() => {
            let typingDiv = document.querySelector('.typingIndicator');
            if (typingDiv) typingDiv.innerHTML = ""
        }, 3000);
    }
    $("#sendMessageBtn").click(() => {
        if ($(".chatInput").val()) {
            fetch("")
        }
    })

    function sendChatMessage() {
        let from_id = "<?php echo $_SESSION['user_id'] ?>";
        console.log(from_id);

    }


    function exportUsers(format) {
        fetch(`export.php?format=${format}&type='users'`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `users_export.${format}`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            })
            .catch(error => console.error('Export failed:', error));
    }
</script>
<script>
    var statusModal = document.getElementById('statusModal');

    $(document).ready(function() {
        // $('#statusModal .modal-body').text('<?php echo addslashes($message ?? ""); ?>');
        $('#statusModal').modal('show');
    });
</script>
<script>
    const chatInput = document.querySelector('.chatInput');
    const chatContainer = document.querySelector('.chat-container');
    $(".support").click(() => {
        chatContainer.toggleAttribute("hidden")
    })
    chatInput ? chatInput.addEventListener('input', () => {
        chatInput.style.height = 'auto'; // Reset height
        chatInput.style.height = chatInput.scrollHeight + 'px'; // Adjust to scroll height
    }) : "";
</script>
</div>
</body>

</html>