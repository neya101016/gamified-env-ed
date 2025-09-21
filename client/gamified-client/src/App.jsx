import { useEffect, useState } from "react";
import axios from "axios";

function App() {
  const [games, setGames] = useState([]);

  useEffect(() => {
    axios.get("http://localhost:4000/api/games")
      .then(res => setGames(res.data))
      .catch(err => console.error(err));
  }, []);

  return (
    <div style={{ padding: "20px" }}>
      <h1>Gamified Environment App</h1>
      
      <ul>
        {games.map(game => <li key={game.id}>{game.name}</li>)}
      </ul>
    </div>
  );
}

export default App;
