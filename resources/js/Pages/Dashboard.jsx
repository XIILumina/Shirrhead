import React, { useEffect, useState } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const Dashboard = () => {
  const [queueStatus, setQueueStatus] = useState("Not in queue");
  const [inQueue, setInQueue] = useState(false);
  const [inviteCode, setInviteCode] = useState("");
  const [queueTime, setQueueTime] = useState(null);
  const [queueCount, setQueueCount] = useState(0);

  // Poll the queue for updates
  useEffect(() => {
    const fetchQueue = async () => {
      try {
        const response = await axios.get("/queue"); // API endpoint for queue status
        setQueueCount(response.data.queue_count);
        setQueueTime(response.data.queue_time);
      } catch (error) {
        console.error("Error fetching queue:", error);
      }
    };

    const interval = setInterval(fetchQueue, 1000); // Poll every 1 second
    return () => clearInterval(interval); // Cleanup interval
  }, []);

  const handleJoinQueue = async () => {
    try {
      const response = await axios.post("/queue/join");
      setQueueStatus(response.data.message);
      setInQueue(true);
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  const handleLeaveQueue = async () => {
    try {
      const response = await axios.post("/queue/leave");
      setQueueStatus(response.data.message);
      setInQueue(false);
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  const handleCreateGame = async () => {
    try {
      const response = await axios.post("/game/create");
      if (response.data.game_id) {
        window.location.href = `/game/${response.data.game_id}`;
      }
    } catch (error) {
      console.error("Error creating game:", error);
    }
  };

  const handleJoinByInviteCode = async () => {
    if (!inviteCode) {
      alert("Please enter a valid invite code.");
      return;
    }

    try {
      const response = await axios.post("/game/join-by-invite", { invite_code: inviteCode });
      if (response.data.success) {
        window.location.href = `/game/${response.data.game_id}`;
      } else {
        alert("Failed to join the game. Invalid invite code.");
      }
    } catch (error) {
      console.error("Error joining game:", error);
      alert("An error occurred while joining the game.");
    }
  };

  return (
    <AuthenticatedLayout>
      <div className="bg-gray-900 text-white p-8 rounded-xl">
        <h1 className="text-4xl text-center mb-4">Welcome to Shithead!</h1>
        <p className={`text-xl text-center mb-6 ${inQueue ? "text-yellow-400" : "text-green-500"}`}>
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

        {/* Join by Invite Code */}
        <div className="mt-8 text-center">
          <h2 className="text-2xl mb-4">Join by Invite Code</h2>
          <input
            type="text"
            value={inviteCode}
            onChange={(e) => setInviteCode(e.target.value)}
            placeholder="Enter invite code"
            className="px-4 py-2 rounded-lg bg-gray-700 text-white border-none mb-4"
          />
          <button
            onClick={handleJoinByInviteCode}
            className="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-transform transform hover:scale-105"
          >
            Join Game
          </button>
        </div>

        {/* Queue Information (Only visible when queue has people) */}
        {queueCount > 0 && (
          <div className="mt-8 text-center">
            <h2 className="text-2xl mb-4">Queue Info</h2>
            <p className="text-lg">People in queue: {queueCount}</p>
            <p className="text-lg">Time in queue: {queueTime || "0 seconds"}</p>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
};

export default Dashboard;
