const express = require("express");
const cors = require("cors");

const app = express();
app.use(cors()); // <-- Enable CORS for all routes
app.use(express.json()); // <-- Parse JSON requests
