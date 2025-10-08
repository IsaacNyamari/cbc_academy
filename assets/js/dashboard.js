document.addEventListener("DOMContentLoaded", function () {
  // Enable tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Enable popovers
  var popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // Charts - Example using Chart.js
  var ctx = document.getElementById("progressChart");
  if (ctx) {
    var progressChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Completed", "In Progress", "Not Started"],
        datasets: [
          {
            data: [65, 15, 20],
            backgroundColor: ["#28a745", "#ffc107", "#dc3545"],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutoutPercentage: 70,
        legend: {
          position: "bottom",
        },
      },
    });
  }

  // AJAX for marking topics as complete
  document.querySelectorAll(".mark-complete-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const topicId = this.dataset.topicId;
      const button = this;

      fetch("../../ajax/mark_complete.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `topic_id=${topicId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            button.innerHTML = '<i class="fas fa-check"></i> Completed';
            button.classList.remove("btn-primary");
            button.classList.add("btn-success");
            button.disabled = true;

            // Update progress display
            const progressElement = document.querySelector(
              `.progress-display[data-topic-id="${topicId}"]`
            );
            if (progressElement) {
              progressElement.innerHTML =
                '<span class="badge bg-success">Completed</span>';
            }

            showToast("Topic marked as completed!", "success");
          } else {
            showToast("Error: " + data.message, "danger");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("An error occurred. Please try again.", "danger");
        });
    });
  });
  // Toast helper
  function showToast(message, type = "info") {
    let toastContainer = document.getElementById("toast-container");
    if (!toastContainer) {
      toastContainer = document.createElement("div");
      toastContainer.id = "toast-container";
      toastContainer.style.position = "fixed";
      toastContainer.style.top = "20px";
      toastContainer.style.right = "20px";
      toastContainer.style.zIndex = "9999";
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${type} border-0 show`;
    toast.role = "alert";
    toast.ariaLive = "assertive";
    toast.ariaAtomic = "true";
    toast.style.minWidth = "200px";
    toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
    toastContainer.appendChild(toast);

    // Bootstrap toast
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    toast.querySelector(".btn-close").onclick = () => {
      bsToast.hide();
    };

    toast.addEventListener("hidden.bs.toast", () => {
      toast.remove();
    });
  }
  // Toggle sidebar on mobile
  document.querySelector(".navbar-toggler")
    ? document
      .querySelector(".navbar-toggler")
      .addEventListener("click", function () {
        document.querySelector(".sidebar").classList.toggle("collapsed");
      })
    : null;
});
window.addEventListener("load", function () {
  const loader = document.getElementById("pageLoader");
  if (loader) {
    loader.classList.add("hidden");
    setTimeout(() => (loader.style.display = "none"), 500);
  }
});
document.getElementById("printReceipt")
  ? document
    .getElementById("printReceipt")
    .addEventListener("click", function () {
      const printContents =
        document.getElementById("receiptContent").innerHTML;
      const originalContents = document.body.innerHTML;

      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      location.reload(); // optional: reload page to restore original state
    })
  : "";

document.getElementById("downloadPDF")
  ? document
    .getElementById("downloadPDF")
    .addEventListener("click", function () {
      const element = document.getElementById("receiptContent");
      const opt = {
        margin: 0.3,
        filename: "payment_receipt.pdf",
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: "in", format: "letter", orientation: "portrait" },
      };
      html2pdf().set(opt).from(element).save();
    })
  : "";
let editUserDetailsButton = document.querySelectorAll("#editUserDetailsButton");
editUserDetailsButton?.forEach((button) => {
  button.addEventListener("click", () => {
    const user_id = Number(button.getAttribute("data-id"));
    if (!user_id) return false;

    fetch("../users/get_user.php", {
      method: "POST",
      body: JSON.stringify({ user_id: user_id }),
    })
      .then((res) => res.json())
      .then((data) => {
        // Populate form fields
        document.getElementById('editUsername').value = data.username;
        document.getElementById('editEmail').value = data.email;
        document.getElementById('editFullName').value = data.full_name;
        document.getElementById('editRole').value = data.role;
        document.getElementById('editSubscriptionPlan').value = data.subscription_plan;
        document.getElementById('editSubscriptionStatus').value = data.subscription_status;
        document.getElementById('editIsActive').checked = data.is_active === "1";
        document.getElementById('editUserId').value = data.id;

        // Update modal title
        document.getElementById('modalTitleId').textContent = `Edit ${data.full_name}'s Details`;

        // // Show modal
        // const modal = new bootstrap.Modal(document.getElementById('editUserDetailsModal'));
        // modal.show();
      })
      .catch((err) => {
        console.error(err);
        alert('Failed to load user data');
      });
  });
});

// Save functionality
document.getElementById('saveUserDetails')?.addEventListener('click', () => {
  const formData = new FormData(document.getElementById('editUserForm'));
  const userData = Object.fromEntries(formData);

  fetch('../users/update_user.php', {
    method: 'POST',
    body: JSON.stringify({
      user_id: parseInt(userData.user_id),
      username: userData.username,
      email: userData.email,
      full_name: userData.full_name,
      role: userData.role,
      subscription_plan: userData.subscription_plan,
      subscription_status: userData.subscription_status,
      is_active: userData.is_active === 'on' // Convert checkbox value to boolean
    }),
    headers: {
      'Content-Type': 'application/json'
    }
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Close modal and refresh data or show success message
        bootstrap.Modal.getInstance(document.getElementById('editUserDetailsModal')).hide();
        alert('User updated successfully');
        // Optionally refresh your user list here
      } else {
        alert('Error updating user: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to update user');
    });
});
(() => {
  "use strict";

  const storedTheme = localStorage.getItem("theme");

  const getPreferredTheme = () => {
    if (storedTheme) {
      return storedTheme;
    }

    return window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  };

  const setTheme = function (theme) {
    if (
      theme === "auto" &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      document.documentElement.setAttribute("data-bs-theme", "dark");
    } else {
      document.documentElement.setAttribute("data-bs-theme", theme);
    }
  };

  setTheme(getPreferredTheme());

  const showActiveTheme = (theme, focus = false) => {
    const themeSwitcher = document.querySelector("#bd-theme");

    if (!themeSwitcher) {
      return;
    }

    const themeSwitcherText = document.querySelector("#bd-theme-text");
    const activeThemeIcon = document.querySelector(".theme-icon-active i");
    const btnToActive = document.querySelector(
      `[data-bs-theme-value="${theme}"]`
    );
    const svgOfActiveBtn = btnToActive.querySelector("i").getAttribute("class");

    for (const element of document.querySelectorAll("[data-bs-theme-value]")) {
      element.classList.remove("active");
      element.setAttribute("aria-pressed", "false");
    }

    btnToActive.classList.add("active");
    btnToActive.setAttribute("aria-pressed", "true");
    activeThemeIcon.setAttribute("class", svgOfActiveBtn);
    const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`;
    themeSwitcher.setAttribute("aria-label", themeSwitcherLabel);

    if (focus) {
      themeSwitcher.focus();
    }
  };

  window
    .matchMedia("(prefers-color-scheme: dark)")
    .addEventListener("change", () => {
      if (storedTheme !== "light" || storedTheme !== "dark") {
        setTheme(getPreferredTheme());
      }
    });

  window.addEventListener("DOMContentLoaded", () => {
    showActiveTheme(getPreferredTheme());

    for (const toggle of document.querySelectorAll("[data-bs-theme-value]")) {
      toggle.addEventListener("click", () => {
        const theme = toggle.getAttribute("data-bs-theme-value");
        localStorage.setItem("theme", theme);
        setTheme(theme);
        showActiveTheme(theme, true);
      });
    }
  });
})();