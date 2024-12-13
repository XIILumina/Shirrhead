import React, { useEffect, useState } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const Dashboard = () => {
  const [queueStatus, setQueueStatus] = useState("Not in queue");
  const [queue, setQueue] = useState([]);
  const [hostedGameId, setHostedGameId] = useState(null);
  const [inQueue, setInQueue] = useState(false); // Track if user is in queue

  // Poll the queue for updates
  useEffect(() => {
    const fetchQueue = async () => {
      try {
        const response = await axios.get("/queue"); // API endpoint for queue status
        setQueue(response.data.queue);
      } catch (error) {
        console.error("Error fetching queue:", error);
      }
    };

    const interval = setInterval(fetchQueue, 1000); // Poll queue every 1 second
    return () => clearInterval(interval); // Cleanup interval on unmount
  }, []);

  // Handle joining the queue
  const handleJoinQueue = async () => {
    try {
      const response = await axios.post("/queue/join"); // API endpoint for joining the queue
      setQueueStatus(response.data.message);
      setInQueue(true); // Mark user as in queue

      if (response.data.game_id) {
        window.location.href = `/game/${response.data.game_id}`;
      }
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  // Handle leaving the queue
  const handleLeaveQueue = async () => {
    try {
      const response = await axios.post("/queue/leave"); // API endpoint for leaving the queue
      setQueueStatus(response.data.message);
      setInQueue(false); // Mark user as not in queue
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  // Handle creating a hosted game
  const handleCreateGame = async () => {
    try {
      const response = await axios.post("/game/create", {
        name: "My Hosted Game",
      });
  
      // Redirect to the game page
      if (response.data.game_id) {
        window.location.href = `/game/${response.data.game_id}`; // Redirect to the game page
      }
    } catch (error) {
      console.error("Error creating game:", error);
    }
  };

  return (
    <AuthenticatedLayout>
      <div className="bg-gray-900 text-white p-8 rounded-xl">
        <h1 className="text-4xl text-center mb-4">Welcome to Shithead!</h1>
        <p
          className={`text-xl text-center mb-6 ${
            inQueue ? "text-yellow-400" : "text-green-500"
          }`}
        >
          {queueStatus}
        </p>
        <div className="flex justify-center gap-4 mb-6">
          {!inQueue ? (
            <button
              onClick={handleJoinQueue}
              className="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-transform transform hover:scale-105"
            >
              Join Quick Match
            </button>
          ) : (
            <button
              onClick={handleLeaveQueue}
              className="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-transform transform hover:scale-105"
            >
              Leave Quick Match
            </button>
          )}
          <button
            onClick={handleCreateGame}
            className="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-transform transform hover:scale-105"
          >
            Create Hosted Game
          </button>
        </div>
        <div className="mt-8">
          <h2 className="text-2xl text-center mb-4">Current Queue</h2>
          <ul className="space-y-4">
            {queue.map((player, index) => (
              <li
                key={index}
                className="bg-gray-700 text-white p-4 rounded-lg transform transition-all duration-300 ease-out hover:scale-105"
              >
                Player {player.user_id}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </AuthenticatedLayout>
  );
};

export default Dashboard;
