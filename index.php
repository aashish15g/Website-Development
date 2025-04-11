<?php
// Database connection and session configuration
session_start();
$db_host = 'localhost';
$db_user = 'root'; // Replace with your database username
$db_pass = ''; // Replace with your database password
$db_name = 'legalconnect';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// Create lawyers table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS lawyers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(50) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    contact VARCHAR(50),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating lawyers table: " . $conn->error);
}

// Populate lawyers table with sample data if empty
$result = $conn->query("SELECT COUNT(*) as count FROM lawyers");
$row = $result->fetch_assoc();
// Create laws table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS laws (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating laws table: " . $conn->error);
}

// Populate laws table with sample data if empty
$result = $conn->query("SELECT COUNT(*) as count FROM laws");
$row = $result->fetch_assoc();

// API endpoints handling
$response = ['status' => 'error', 'message' => 'Invalid request'];

// Handle signup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($name) || empty($email) || empty($password)) {
        $response = ['status' => 'error', 'message' => 'All fields are required'];
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'User created successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Email already exists or database error'];
        }
        $stmt->close();
    }
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $response = ['status' => 'error', 'message' => 'Email and password are required'];
    } else {
        // Get user
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                $response = ['status' => 'success', 'message' => 'Login successful'];
                
                // Redirect to member area
                if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?area=member&page=home');
                    exit;
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid password'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'User not found'];
        }
        $stmt->close();
    }
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Handle API request for searching lawyers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'searchLawyers') {
    $query = isset($_GET['query']) ? "%" . $_GET['query'] . "%" : "%%";
    
    $stmt = $conn->prepare("SELECT name, location, specialization, contact FROM lawyers WHERE 
        name LIKE ? OR location LIKE ? OR specialization LIKE ?");
    $stmt->bind_param("sss", $query, $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lawyers = [];
    while ($row = $result->fetch_assoc()) {
        $lawyers[] = $row;
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($lawyers);
    exit;
}

// Handle API request for searching laws
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'searchLaws') {
  $query = isset($_GET['query']) ? "%" . $_GET['query'] . "%" : "%%";
  
  try {
      // Prepare and execute the query
      $stmt = $conn->prepare("SELECT name, description, category FROM laws WHERE 
          name LIKE ? OR description LIKE ? OR category LIKE ?");
      if (!$stmt) {
          throw new Exception("Prepare failed: " . $conn->error);
      }
      
      $stmt->bind_param("sss", $query, $query, $query);
      
      if (!$stmt->execute()) {
          throw new Exception("Execute failed: " . $stmt->error);
      }
      
      $result = $stmt->get_result();
      
      $laws = [];
      while ($row = $result->fetch_assoc()) {
          $laws[] = $row;
      }
      $stmt->close();
      
      header('Content-Type: application/json');
      echo json_encode($laws);
  } catch (Exception $e) {
      header('Content-Type: application/json');
      echo json_encode(['error' => $e->getMessage()]);
  }
  exit;
}
// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Destroy session
    session_unset();
    session_destroy();
    
    // Redirect to home
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get current area (public/member) and page
$current_area = isset($_SESSION['user_id']) ? (isset($_GET['area']) ? $_GET['area'] : 'member') : 'public';
$current_page = isset($_GET['page']) ? $_GET['page'] : ($current_area === 'public' ? 'signin' : 'home');

// Force public area if not logged in
if (!isset($_SESSION['user_id']) && $current_area === 'member') {
    $current_area = 'public';
    $current_page = 'signin';
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Legal Connect</title>
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap"
    />
    <style>
      body {
        font-family: "Poppins", sans-serif;
        margin: 0;
        padding: 0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        color: white;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);
        transition: background-image 0.5s ease-in-out;
      }
      html {
        scroll-behavior: smooth;
      }
      nav {
        background-color: rgba(0, 0, 0, 0.8);
        padding: 15px;
        text-align: center;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
      }
      nav a {
        color: white;
        margin: 0 15px;
        text-decoration: none;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
      }
      nav a:hover,
      nav a.active {
        color: #f39c12;
        border-bottom: 2px solid #f39c12;
      }
      .container {
        display: none;
        background-color: rgba(0, 0, 0, 0.85);
        color: white;
        padding: 40px;
        border-radius: 15px;
        margin: 80px auto 40px;
        width: 90%;
        max-width: 800px;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s, transform 0.5s;
      }
      .container.show {
        display: block;
        opacity: 1;
        transform: translateY(0);
      }
      input,
      button,
      textarea {
        display: block;
        width: calc(100% - 40px);
        margin: 10px auto;
        padding: 15px;
        border-radius: 8px;
        border: none;
        font-size: 16px;
      }
      button {
        background-color: #f39c12;
        color: white;
        cursor: pointer;
        transition: background 0.3s;
      }
      button:hover {
        background-color: #d35400;
      }
      .welcome-message {
        text-align: right;
        margin-right: 15px;
      }
      @media (max-width: 768px) {
        nav {
          display: flex;
          flex-direction: column;
          padding: 10px;
        }
        nav a {
          padding: 10px;
          display: block;
        }
        .container {
          width: 95%;
          padding: 20px;
        }
        .welcome-message {
          text-align: center;
          margin: 10px 0;
        }
      }
    </style>
  </head>
  <body>
    <!-- PUBLIC AREA - Only Sign In/Sign Up and Indian Law Library -->
    <?php if ($current_area === 'public'): ?>
    <nav>
      <a onclick="navigateTo('signin')" class="nav-link <?php echo $current_page === 'signin' ? 'active' : ''; ?>">Sign In / Sign Up</a>
      <a onclick="navigateTo('law-library')" class="nav-link <?php echo $current_page === 'law-library' ? 'active' : ''; ?>">Indian Law Library</a>
    </nav>

    <div id="signin" class="container <?php echo $current_page === 'signin' ? 'show' : ''; ?>">
      <h2>Sign In</h2>
      <form id="login-form" method="post">
        <input type="hidden" name="action" value="login">
        <input type="email" id="signin-email" name="email" placeholder="Enter Email" required>
        <input type="password" id="signin-password" name="password" placeholder="Enter Password" required>
        <button type="submit">Sign In</button>
      </form>
      <p id="signin-message" style="color: #f39c12;">
        <?php 
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
            echo $response['status'] === 'error' ? '❌ ' . $response['message'] : '✅ ' . $response['message'];
          }
        ?>
      </p>
      <p>Don't have an account? <a onclick="navigateTo('signup')" style="cursor:pointer; color:#f39c12;">Sign Up</a></p>
    </div>
    
    <div id="signup" class="container <?php echo $current_page === 'signup' ? 'show' : ''; ?>">
      <h2>Sign Up</h2>
      <form id="signup-form" method="post">
        <input type="hidden" name="action" value="signup">
        <input type="text" id="signup-name" name="name" placeholder="Enter Name" required>
        <input type="email" id="signup-email" name="email" placeholder="Enter Email" required>
        <input type="password" id="signup-password" name="password" placeholder="Enter Password" required>
        <button type="submit">Sign Up</button>
      </form>
      <p id="signup-message" style="color: #f39c12;">
        <?php 
          if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
            echo $response['status'] === 'error' ? '❌ ' . $response['message'] : '✅ ' . $response['message'];
          }
        ?>
      </p>
      <p>Already have an account? <a onclick="navigateTo('signin')" style="cursor:pointer; color:#f39c12;">Sign In</a></p>
    </div>
    
    <div id="law-library" class="container <?php echo $current_page === 'law-library' ? 'show' : ''; ?>">
      <h2>Indian Law Library</h2>
      <p>Search for Indian laws, acts, and legal provisions.</p>
      <input type="text" id="law-search" placeholder="Search for a law..." />
      <button onclick="searchLaws()">Search</button>
      <div id="law-results"></div>
    </div>
    
    <!-- MEMBER AREA - Dashboard with all tabs -->
    <?php elseif ($current_area === 'member'): ?>
    <nav>
      <a onclick="navigateTo('home')" class="nav-link <?php echo $current_page === 'home' ? 'active' : ''; ?>">Home</a>
      <a onclick="navigateTo('lawyers')" class="nav-link <?php echo $current_page === 'lawyers' ? 'active' : ''; ?>">Find Lawyers</a>
      <a onclick="navigateTo('forum')" class="nav-link <?php echo $current_page === 'forum' ? 'active' : ''; ?>">Forum</a>
      <a onclick="navigateTo('law-library')" class="nav-link <?php echo $current_page === 'law-library' ? 'active' : ''; ?>">Indian Law Library</a>
      <a href="?action=logout" class="nav-link">Sign Out</a>
      <span class="welcome-message">Welcome, <?php echo $_SESSION['user_name']; ?></span>
    </nav>

    <div id="home" class="container <?php echo $current_page === 'home' ? 'show' : ''; ?>">
      <h1>Welcome to Legal Connect</h1>
      <p>
        Find lawyers, access legal resources, and discuss legal matters easily.
      </p>
      <p>Hello, <?php echo $_SESSION['user_name']; ?>! You're now logged in.</p>
    </div>

    <div id="lawyers" class="container <?php echo $current_page === 'lawyers' ? 'show' : ''; ?>">
      <h2>Find Lawyers</h2>
      <input
        type="text"
        id="lawyer-search"
        placeholder="Enter Location or Specialization"
      />
      <button onclick="searchLawyers()">Search</button>
      <div id="lawyer-results"></div>
    </div>

    <div id="forum" class="container <?php echo $current_page === 'forum' ? 'show' : ''; ?>">
      <h2>Legal Forum</h2>
      <p>Engage in legal discussions and seek guidance.</p>
      <textarea id="forum-question" placeholder="Type your question..."></textarea>
      <button onclick="postQuestion()">Post Inquiry</button>
      <div id="forum-posts"></div>
    </div>
    
    <div id="law-library" class="container <?php echo $current_page === 'law-library' ? 'show' : ''; ?>">
      <h2>Indian Law Library</h2>
      <p>Search for Indian laws, acts, and legal provisions.</p>
      <input type="text" id="law-search" placeholder="Search for a law..." />
      <button onclick="searchLaws()">Search</button>
      <div id="law-results"></div>
    </div>
    <?php endif; ?>
    
    <script>
      // Background images
      const backgrounds = {
        home: "url('https://plus.unsplash.com/premium_photo-1698084059560-9a53de7b816b?w=700&auto=format&fit=crop&q=60')",
        lawyers: "url('https://plus.unsplash.com/premium_photo-1661497281000-b5ecb39a2114?w=700&auto=format&fit=crop&q=60')",
        forum: "url('https://plus.unsplash.com/premium_photo-1661547944387-96fffefa0b8f?w=700&auto=format&fit=crop&q=60')",
        "law-library": "url('https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=700&auto=format&fit=crop&q=60')",
        signin: "url('https://plus.unsplash.com/premium_photo-1674727219372-4ba6644106bc?w=700&auto=format&fit=crop&q=60')",
        signup: "url('https://images.unsplash.com/photo-1419640303358-44f0d27f48e7?w=700&auto=format&fit=crop&q=60')",
      };

      // Navigation function
      function navigateTo(sectionId) {
        // Check if we're logged in
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        
        // Update URL with GET parameters
        let url = window.location.pathname;
        url += '?';
        
        if (isLoggedIn) {
          url += 'area=member&page=' + sectionId;
        } else {
          url += 'area=public&page=' + sectionId;
        }
        
        window.history.pushState({}, '', url);
        
        // Hide all containers
        document.querySelectorAll(".container").forEach((section) => {
          section.classList.remove("show");
        });

        // Remove active class from all nav links
        document.querySelectorAll("nav a").forEach((link) => {
          link.classList.remove("active");
        });

        // Show the selected container
        setTimeout(() => {
          document.getElementById(sectionId).classList.add("show");
          document.body.style.backgroundImage = backgrounds[sectionId] || backgrounds.home;
          
          // Find and activate the correct nav link
          document.querySelectorAll(".nav-link").forEach(link => {
            if (link.textContent.toLowerCase().includes(sectionId.replace('-', ' '))) {
              link.classList.add("active");
            }
          });

          // Load forum posts when navigating to the forum
          if (sectionId === "forum") {
            displayForumPosts();
          }
        }, 300);
      }
      
      // Search lawyers function - now fetches from database
      function searchLawyers() {
        let query = document.getElementById("lawyer-search").value.trim();
        let resultsDiv = document.getElementById("lawyer-results");
        resultsDiv.innerHTML = "<p>Searching...</p>";

        // Fetch lawyers from the database via AJAX
        fetch(`${window.location.pathname}?action=searchLawyers&query=${encodeURIComponent(query)}`)
          .then(response => response.json())
          .then(lawyers => {
            resultsDiv.innerHTML = "";
            
            if (lawyers.length === 0) {
              resultsDiv.innerHTML = "<p>No lawyers found.</p>";
              return;
            }
            
            lawyers.forEach(lawyer => {
              let lawyerCard = document.createElement("div");
              lawyerCard.style.padding = "10px";
              lawyerCard.style.margin = "10px 0";
              lawyerCard.style.border = "1px solid #f39c12";
              lawyerCard.style.borderRadius = "8px";
              
              let contactInfo = lawyer.contact ? `<br>Contact: ${lawyer.contact}` : '';
              lawyerCard.innerHTML = `<strong>${lawyer.name}</strong> - ${lawyer.specialization} (${lawyer.location})${contactInfo}`;
              
              resultsDiv.appendChild(lawyerCard);
            });
          })
          .catch(error => {
            resultsDiv.innerHTML = "<p>Error fetching lawyer data.</p>";
            console.error('Error:', error);
          });
      }

      // Improved searchLaws function
      function searchLaws() {
        let query = document.getElementById("law-search").value.trim();
        let resultsDiv = document.getElementById("law-results");
        
        if (!resultsDiv) return;
        
        resultsDiv.innerHTML = "<p>Searching...</p>";

        // Fetch laws from the database via AJAX
        fetch(`${window.location.pathname}?action=searchLaws&query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                resultsDiv.innerHTML = "";
                
                // Check if there's an error message
                if (data.error) {
                    resultsDiv.innerHTML = `<p>Error: ${data.error}</p>`;
                    return;
                }
                
                // Process the laws array
                if (data.length === 0) {
                    resultsDiv.innerHTML = "<p>No matching laws found.</p>";
                    return;
                }
                
                data.forEach(law => {
                    let lawDiv = document.createElement("div");
                    lawDiv.style.padding = "10px";
                    lawDiv.style.margin = "10px 0";
                    lawDiv.style.border = "1px solid #f39c12";
                    lawDiv.style.borderRadius = "8px";
                    
                    let categoryInfo = law.category ? `<br>Category: ${law.category}` : '';
                    lawDiv.innerHTML = `<strong>${law.name}</strong>: ${law.description}${categoryInfo}`;
                    
                    resultsDiv.appendChild(lawDiv);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                resultsDiv.innerHTML = "<p>Error fetching law data. Please try again later.</p>";
            });
      }

      // Forum posts array
      let forumPosts = [];

      // Forum functions
      function displayForumPosts() {
        let postsDiv = document.getElementById("forum-posts");
        if (!postsDiv) return;

        postsDiv.innerHTML = ""; // Clear existing posts before reloading

        forumPosts.forEach((post) => {
          let postDiv = document.createElement("div");
          postDiv.style.padding = "10px";
          postDiv.style.margin = "10px 0";
          postDiv.style.border = "1px solid #f39c12";
          postDiv.style.borderRadius = "8px";
          postDiv.innerHTML = `<strong>${post.user}:</strong> ${post.question}`;
          postsDiv.appendChild(postDiv);
        });
      }

      function postQuestion() {
        let questionInput = document.getElementById("forum-question");
        let questionText = questionInput.value.trim();
        
        if (questionText === "") {
          alert("Please enter a valid question.");
          return;
        }
        
        // Use session username if available, otherwise Anonymous
        const username = "<?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Anonymous'; ?>";
        forumPosts.push({ user: username, question: questionText });
        
        questionInput.value = ""; // Clear input
        displayForumPosts(); // Refresh displayed posts
      }

      // Initialize page on load
      document.addEventListener('DOMContentLoaded', () => {
        // Get current page from URL or use default
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page');
        
        if (page) {
          // If page is specified in URL, navigate to it
          const element = document.getElementById(page);
          if (element) {
            document.body.style.backgroundImage = backgrounds[page] || backgrounds.home;
          }
        } else {
          // Default background for initial load
          <?php if ($current_area === 'public'): ?>
          document.body.style.backgroundImage = backgrounds.signin;
          <?php else: ?>
          document.body.style.backgroundImage = backgrounds.home;
          <?php endif; ?>
        }
        
        // If there was a login/signup action and it was successful, handle redirection
        <?php if (($_SERVER['REQUEST_METHOD'] === 'POST') && isset($response['status']) && $response['status'] === 'success'): ?>
          <?php if (isset($_POST['action']) && $_POST['action'] === 'signup'): ?>
            setTimeout(() => navigateTo('signin'), 1000);
          <?php endif; ?>
        <?php endif; ?>
      });
    </script>
  </body>
</html>