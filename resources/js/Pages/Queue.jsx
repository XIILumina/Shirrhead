import React, { useEffect, useState } from "react";
import axios from "axios";


export default function Queue() {
  // component code here

  const [queue, setQueue] = useState([]);

  useEffect(() => {
    const fetchQueue = async () => {
      try {
        const response = await axios.get("/Queue");
        setQueue(response.data.queue);
      } catch (error) {
        console.error("Error fetching queue:", error);
      }
    };

    const interval = setInterval(fetchQueue, 5000); // Poll queue every 1 seconds
    return () => clearInterval(interval); // Clean up on component unmount
  }, []);

  
    <div className="queue">
      <h2>Current Queue</h2>
      <ul>
        {queue.map((player, index) => (
          <li key={index}>Player {player.user_id}</li>
        ))}
      </ul>
    </div>
};

