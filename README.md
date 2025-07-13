
## 📂 Project Structure
├── prs-api/ # Backend (PHP + MySQL + REST API)
├── prs-frontend/ # Frontend (HTML, CSS, JS + Chart.js)
├── SCREENSHOTS
├── SQL(database)


# 💻 Requirements
Before running the project, make sure you have:

- [XAMPP](https://www.apachefriends.org/) – Apache & MySQL local server
- [Visual Studio Code](https://code.visualstudio.com/) – Code editor
- [Ngrok](https://ngrok.com/) – To create HTTPS tunnels for backend testing
- Git (optional) – To clone or update the repository
- Web browser (Chrome/Firefox recommended)

## Security
JWT-based Auth: HS256 algorithm, 1-hour expiry
Input Validation: Against SQLi, XSS, CSRF
Password Hashing: SHA-256 + bcrypt
HTTPS with Ngrok: Simulates real-world TLS
Audit Logs: Tracks user changes/actions
Role-Based Access Control: 3 user types with scoped permissions


### 📊 Dashboard Visuals (Chart.js)
- 📈 Line chart: Vaccination trends over time
- 📊 Bar chart: Vaccination distribution by region
- 🥧 Pie chart: Breakdown of vaccine types

🗂️ How to Run Locally
1. Clone the Repository
git clone https://github.com/sot-a4/pandemic-resilience-system.git

2. Set Up the Project
Install XAMPP and start Apache/MySQL
Install Visual Studio Code to edit files
Install Ngrok to tunnel your local backend (optional)

3. File Setup
Move the prs-api/ and prs-frontend/ folders into:
C:/xampp/htdocs/pandemic-resilience-system/

4. Import the Database
Visit: http://localhost/phpmyadmin
Create a database (e.g. prs_db)
Import your SQL dump (e.g. prs-api/db/schema.sql)

5. Start Ngrok (Optional)
If you want HTTPS:
ngrok http 80
Use the generated Ngrok HTTPS URL in script.js and register.html
