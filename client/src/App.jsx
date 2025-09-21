import { useEffect, useState } from "react";
import axios from "axios";
import { Howl } from "howler";
import clickSound from "./assets/click.mp3";

function App() {
  const [games, setGames] = useState([]);

  // Fetch games from backend
  useEffect(() => {
    axios.get("http://localhost:4000/api/games")
      .then(res => setGames(res.data))
      .catch(err => console.error(err));
  }, []);

  // Play sound on button click
  const playSound = () => {
    const sound = new Howl({ src: [clickSound] });
    sound.play();
  };

  return (
    <div style={{ padding: "20px", fontFamily: "Arial, sans-serif" }}>
      <h1>🌱 Gamified Environment App</h1>
      <ul>
        {games.map(game => (
          <li key={game.id}>
            {game.name} - {game.points} pts
            <button onClick={playSound} style={{ marginLeft: "10px" }}>Play</button>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default App;
