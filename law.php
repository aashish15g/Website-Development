<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Update if you have a password
$dbname = "legalconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Array of laws
$laws = [
    ["Constitution of India", "The supreme law that establishes the framework of the Indian government"],
    ["Indian Penal Code, 1860", "Comprehensive criminal code of India"],
    ["Code of Criminal Procedure, 1973", "Procedural law for criminal cases"],
    ["Indian Evidence Act, 1872", "Rules of evidence in Indian courts"],
    ["Code of Civil Procedure, 1908", "Procedural law for civil cases"],
    ["Consumer Protection Act, 2019", "Protection of consumer interests"],
    ["Right to Information Act, 2005", "Provides for public access to government information"],
    ["Income Tax Act, 1961", "Governs taxation of income"],
    ["Companies Act, 2013", "Regulates incorporation and management of companies"],
    ["Goods and Services Tax Act, 2017", "Unified tax on supply of goods and services"],
    ["Protection of Children from Sexual Offences Act, 2012 (POCSO)", "Protection of children"],
    ["Juvenile Justice (Care and Protection of Children) Act, 2015", "Deals with juveniles in conflict with law"],
    ["Motor Vehicles Act, 1988", "Regulates all aspects of road transport vehicles"],
    ["Information Technology Act, 2000", "Legal recognition for electronic transactions"],
    ["Prevention of Corruption Act, 1988", "Anti-corruption legislation"],
    ["Prevention of Money Laundering Act, 2002", "To prevent money laundering"],
    ["Foreign Exchange Management Act, 1999", "Regulates foreign exchange transactions"],
    ["The Arbitration and Conciliation Act, 1996", "Alternative dispute resolution"],
    ["National Food Security Act, 2013", "Legal entitlement to subsidized food grains"],
    ["Indian Contract Act, 1872", "Governs contract law in India"],
    ["Hindu Marriage Act, 1955", "Marriage law for Hindus"],
    ["Muslim Personal Law (Shariat) Application Act, 1937", "Personal law for Muslims"],
    ["Special Marriage Act, 1954", "Civil marriage for citizens irrespective of religion"],
    ["Indian Succession Act, 1925", "Laws on inheritance and succession"],
    ["Hindu Succession Act, 1956", "Inheritance laws for Hindus"],
    ["Transfer of Property Act, 1882", "Regulates transfer of property"],
    ["Registration Act, 1908", "Registration of documents related to immovable property"],
    ["Land Acquisition Act, 2013", "Fair compensation for land acquisition"],
    ["Indian Trusts Act, 1882", "Regulation of private trusts"],
    ["Negotiable Instruments Act, 1881", "Governs promissory notes, bills of exchange"],
    ["Sale of Goods Act, 1930", "Buying and selling of goods"],
    ["Partnership Act, 1932", "Rules governing partnership firms"],
    ["Specific Relief Act, 1963", "Enforcement of civil rights"],
    ["Limitation Act, 1963", "Time limits for filing legal suits"],
    ["Insolvency and Bankruptcy Code, 2016", "Insolvency resolution for individuals and companies"],
    ["Banking Regulation Act, 1949", "Regulation of banking companies"],
    ["Securities and Exchange Board of India Act, 1992", "Regulates securities market"],
    ["Competition Act, 2002", "Prevention of anti-competitive practices"],
    ["Patents Act, 1970", "Protection of inventions"],
    ["Copyright Act, 1957", "Protection of literary, dramatic, musical works"],
    ["Trademarks Act, 1999", "Protection of trademarks"],
    ["Designs Act, 2000", "Protection of industrial designs"],
    ["Environmental Protection Act, 1986", "Protection and improvement of environment"],
    ["Wildlife Protection Act, 1972", "Protection of wild animals and birds"],
    ["Air (Prevention and Control of Pollution) Act, 1981", "Prevention of air pollution"],
    ["Water (Prevention and Control of Pollution) Act, 1974", "Prevention of water pollution"],
    ["Factories Act, 1948", "Safety, health and welfare of factory workers"],
    ["Industrial Disputes Act, 1947", "Investigation and settlement of industrial disputes"],
    ["Payment of Wages Act, 1936", "Timely payment of wages to employees"],
    ["Maternity Benefit Act, 1961", "Maternity benefits to women employees"]
];

// Prepare SQL insert statement
$stmt = $conn->prepare("INSERT INTO laws (name, description) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $description);

// Insert each law
foreach ($laws as $law) {
    $name = $law[0];
    $description = $law[1];
    $stmt->execute();
}

echo "Laws inserted successfully.";

$stmt->close();
$conn->close();
?>
