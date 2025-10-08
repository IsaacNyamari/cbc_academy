"USE STRICT"

function getBrowserRootUrl() {
  const location = window.location;
  return location.protocol + '//' + location.host + '/';
}
let q, endPoint = "/api/", target = "requests/", method = "POST";
let originalLocaton = window.location.origin
let root
const rootUrl = getBrowserRootUrl();
const SUBMIT_ENDPOINT = rootUrl + "includes/functions.php";
let xhr = new XMLHttpRequest()
const loginForm = document.getElementById("login")
const registerForm = document.getElementById("register")

loginForm ? loginForm.addEventListener("submit", (e) => {
  e.preventDefault()
  xhr.open("POST", SUBMIT_ENDPOINT);
  const formData = new FormData(loginForm)
  formData.append("action", "login")
  xhr.onload = function () {
    let response = JSON.parse(this.response)
    if (response.status === "success") {
      window.location.href = response.redirectUrl;
    }
  }
  xhr.send(formData)
}) : "";

registerForm ? registerForm.addEventListener("submit", (e) => {
  e.preventDefault()
  xhr.open("POST", SUBMIT_ENDPOINT);
  const formData = new FormData(registerForm)
  formData.append("action", "register")
  xhr.onload = function () {
    let response = JSON.parse(this.response)
    if (response.status == "success") {
      window.location.href = response.redirectUrl;
    }
  }
  xhr.send(formData)
}) : "";
if (window.location.href.includes("landlord")) {
  root = document.getElementById("mainContent");
  fetch(originalLocaton + "/dashboard/landlord/main-area-top.php", {
    method: "GET"
  }).then(res => res.text()).then(data => {
    root.innerHTML = data
  }).catch(error => {
    console.log(error);

  })
}
let navLinks = document.querySelectorAll(".nav-item")
navLinks.forEach(navLink => {
  navLink.addEventListener("click", (e) => {
    e.preventDefault();
    q = navLink.children[0].getAttribute("href");
    fetch(originalLocaton + endPoint + target, {
      method: method,
      body: JSON.stringify({
        query: q
      })
    })
      .then(res => res.json())
      .then(data => {
        root = document.getElementById("mainContent");
        if (data.apartments) {
          let tableHTML = `
                <h2 class="text-center">Apartments</h2>
                <hr>
                    <table class="table table-bordered table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Address</th>
                          <th>Rent (KES)</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                  `;
          data.apartments.forEach(apartment => {
            tableHTML += `
                      <tr>
                        <td>${apartment.id}</td>
                        <td>${apartment.apartment_name}</td>
                        <td>${apartment.address}</td>
                        <td>${apartment.rent_amount}</td>
                        <td>${apartment.status}</td>
                      </tr>
                    `;
          });
          tableHTML += `
                      </tbody>
                    </table>
                  `;
          root.innerHTML = tableHTML;
        } else if (data.houses) {
          let tableHTML = `
                <h2 class="text-center">Houses</h2>
                        <hr>
                    <table class="table table-bordered table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Apartment</th>
                          <th>Address</th>
                          <th>Rent (KES)</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                  `;
          data.houses.forEach(house => {
            tableHTML += `
                      <tr>
                        <td>${house.house_id}</td>
                        <td>${house.house_no}</td>
                        <td>${house.apartment_name}</td>
                        <td>${house.address}</td>
                        <td>${house.rent_amount}</td>
                        <td>${house.house_status}</td>
                      </tr>
                    `;
          });
          tableHTML += `
                      </tbody>
                    </table>
                  `;
          root.innerHTML = tableHTML;
        } else if (data.tenants) {
          let tableHTML = `
                <h2 class="text-center">Tenants</h2>
                        <hr>
                    <table class="table table-bordered table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Phone</th>
                          <th>Apartment</th>
                          <th>House Number</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                  `;
          data.tenants.forEach(tenant => {
            tableHTML += `
                      <tr>
                        <td>${tenant.id}</td>
                        <td>${tenant.name}</td>
                        <td>${tenant.email}</td>
                        <td>${tenant.phone}</td>
                        <td>${tenant.apartment_name}</td>
                        <td>${tenant.house_no}</td>
                        <td>${tenant.user_status}</td>
                      </tr>
                    `;
          });
          tableHTML += `
                      </tbody>
                    </table>
                  `;
          root.innerHTML = tableHTML;
        } else if (data.caretakers) {
          let tableHTML = `
                <h2 class="text-center">Caretakers</h2>
                        <hr>
                    <table class="table table-bordered table-striped table-hover">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Phone</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                  `;
          data.caretakers.forEach(caretaker => {
            tableHTML += `
                      <tr>
                        <td>${caretaker.id}</td>
                        <td>${caretaker.name}</td>
                        <td>${caretaker.email}</td>
                        <td>${caretaker.phone}</td>
                        <td>${caretaker.user_status}</td>
                      </tr>
                    `;
          });
          tableHTML += `
                      </tbody>
                    </table>
                  `;
          root.innerHTML = tableHTML;
        } else if (data.content) {
          root.innerHTML = data.content;
        }
      })
      .catch(error => {
        console.error("Fetch error:", error);
      });
  });
});

// add data to the database
// Utility function to send form data to the endpoint
function submitFormData(formData, action, callback) {
  formData.append("action", action);
  fetch(originalLocaton + endPoint + target, {
    method: "POST",
    body: formData
  })
    .then(res => res.json())
    .then(callback)
    .catch(err => {
      console.error("Submission error:", err);
      alert("An error occurred. Please try again.");
    });
}

// Water Bill Form
const waterBillForm = document.getElementById("waterBillForm");
if (waterBillForm) {
  waterBillForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData();
    submitFormData(formData, "add_water_bill", function (response) {
      if (response.status === "success") {
        alert("Water bill added successfully!");
        waterBillForm.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("addWaterBillModal")).hide();
      } else {
        alert(response.message || "Failed to add water bill.");
      }
    });
  });
}

// Caretaker Form
const caretakerForm = document.getElementById("caretakerForm");
if (caretakerForm) {
  caretakerForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData();
    submitFormData(formData, "add_caretaker", function (response) {
      console.log(response);      
      if (response.status === "success") {
        alert("Caretaker added successfully!");
        caretakerForm.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("addCaretakerModal")).hide();
      } else {
        alert(response.message || "Failed to add caretaker.");
      }
    });
  });
}

// Tenant Form
const tenantForm = document.getElementById("tenantForm");
if (tenantForm) {
  tenantForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData();
    submitFormData(formData, "add_tenant", function (response) {
      if (response.status === "success") {
        alert("Tenant added successfully!");
        tenantForm.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("addTenantModal")).hide();
      } else {
        alert(response.message || "Failed to add tenant.");
      }
    });
  });
}

// House Form
const houseForm = document.getElementById("houseForm");
if (houseForm) {
  houseForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData();
    submitFormData(formData, "add_house", function (response) {
      if (response.status === "success") {
        alert("House added successfully!");
        houseForm.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("addHouseModal")).hide();
      } else {
        alert(response.message || "Failed to add house.");
      }
    });
  });
}

// Apartment Form
const apartmentForm = document.getElementById("apartmentForm");
if (apartmentForm) {
  apartmentForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData();
    submitFormData(formData, "add_apartment", function (response) {
      if (response.status === "success") {
        alert("Apartment added successfully!");
        apartmentForm.reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("addApartmentModal")).hide();
      } else {
        // alert(response.message || "Failed to add apartment.");
      }
    });
  });
}