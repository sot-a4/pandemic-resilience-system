
const BASE_URL = " https://d2f6-45-66-41-85.ngrok-free.app";


document.addEventListener("DOMContentLoaded", function () {
  const path = window.location.pathname;

  // ---------------------- LOGIN LOGIC ----------------------
  if (path.includes("login.html")) {
    const form = document.getElementById("loginForm");

    if (!form) {
      console.error("Login form not found!");
      return;
    }

    form.addEventListener("submit", async function (e) {
      e.preventDefault();

      const email = form.email.value;
      const password = form.password.value;

      try {
        const response = await fetch(`${BASE_URL}/prs-api/auth`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ email, password }),
        });

        const result = await response.json();

        if (response.ok) {
          localStorage.setItem("token", result.token);
          localStorage.setItem("full_name", result.user.full_name);
          alert(`Welcome, ${result.user.full_name}`);
          window.location.href = "index.html";
        } else {
          alert(result.error || "Login failed");
        }
      } catch (error) {
        console.error("Login request failed:", error);
        alert("An error occurred during login.");
      }
    });
  }

  // ---------------------- DASHBOARD LOGIC ----------------------
  else if (path.includes("index.html")) {
    const token = localStorage.getItem("token");
    const fullName = localStorage.getItem("full_name");

    if (!token) {
      alert("You must log in first!");
      window.location.href = "login.html";
      return;
    }

    // Προβολή ονόματος χρήστη
    const h2 = document.querySelector("h2");
    if (fullName && h2) {
      h2.textContent = `Welcome ${fullName} - Vaccination Insights`;
    }

    fetch(`${BASE_URL}/prs-api/vaccination`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
      .then((res) => res.json())
      .then((data) => {
        const labels = data.map((item) => item.full_name);
        const doses = data.map((item) => item.dose_number);
        const vaccineCounts = {};

        data.forEach((item) => {
          vaccineCounts[item.vaccine_name] =
            (vaccineCounts[item.vaccine_name] || 0) + 1;
        });

        const pieLabels = Object.keys(vaccineCounts);
        const pieData = Object.values(vaccineCounts);

        // Bar Chart
        const barCtx = document.getElementById("barChart").getContext("2d");
        new Chart(barCtx, {
          type: "bar",
          data: {
            labels: labels,
            datasets: [
              {
                label: "Number of Doses",
                data: doses,
                backgroundColor: "rgba(75, 192, 192, 0.5)",
                borderColor: "rgba(75, 192, 192, 1)",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });

        // Pie Chart
        const pieCtx = document.getElementById("pieChart").getContext("2d");
        new Chart(pieCtx, {
          type: "pie",
          data: {
            labels: pieLabels,
            datasets: [
              {
                label: "Vaccine Distribution",
                data: pieData,
                backgroundColor: ["#ff6384", "#36a2eb", "#ffce56", "#8e44ad"],
              },
            ],
          },
          options: {
            responsive: true,
          },
        });

        // Line Chart (π.χ. δόσεις ανά χρήστη)
        const lineCtx = document.getElementById("lineChart").getContext("2d");
        new Chart(lineCtx, {
          type: "line",
          data: {
            labels: labels,
            datasets: [
              {
                label: "Doses over time",
                data: doses,
                fill: false,
                borderColor: "rgb(75, 192, 192)",
                tension: 0.1,
              },
            ],
          },
          options: {
            responsive: true,
          },
        });
      })
      .catch((error) => {
        console.error("Error fetching vaccination data:", error);
        alert("Failed to load vaccination data");
      });
  }
});

