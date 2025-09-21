const express = require("express");
const cors = require("cors");

const app = express();
app.use(cors());
app.use(express.json());

// Sample game data
let games = [
  { id: 1, name: "Recycling Challenge", points: 10 },
  { id: 2, name: "Energy Saver", points: 15 },
  { id: 3, name: "Water Conservation Quiz", points: 20 },
];

// Get all games
app.get("/api/games", (req, res) => {
  res.json(games);
});

// Add new game
app.post("/api/games", (req, res) => {
  const { name, points } = req.body;
  const newGame = { id: games.length + 1, name, points };
  games.push(newGame);
  res.json(newGame);
});

const PORT = 4000;
app.listen(PORT, () => console.log(`Backend running on http://localhost:${PORT}`));
