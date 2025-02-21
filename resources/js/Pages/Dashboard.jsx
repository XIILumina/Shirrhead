import React, { useEffect, useState } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { motion } from "framer-motion";

const Dashboard = () => {
  const [queueStatus, setQueueStatus] = useState("Not in queue");
  const [inQueue, setInQueue] = useState(false);
  const [inviteCode, setInviteCode] = useState("");
  const [showInviteInput, setShowInviteInput] = useState(false);
  const [queueTime, setQueueTime] = useState(null);
  const [queueCount, setQueueCount] = useState(0);
  const [difficulty, setDifficulty] = useState("easy");
  const [isJoined, setIsJoined] = useState(false);

  // Animation variants
  const containerVariants = {
    hidden: { opacity: 0, y: 50 },
    visible: { 
      opacity: 1, 
      y: 0, 
      transition: { 
        duration: 0.8, 
        ease: "easeOut", 
        staggerChildren: 0.2 
      }
    }
  };

  const itemVariants = {
    hidden: { opacity: 0, scale: 0.8 },
    visible: { 
      opacity: 1, 
      scale: 1, 
      transition: { type: "spring", stiffness: 200, damping: 15 }
    }
  };

  const buttonVariants = {
    hover: { scale: 1.1, rotate: 2 },
    tap: { scale: 0.95 }
  };

  useEffect(() => {
    const storedQueueStatus = localStorage.getItem("queueStatus");
    const storedInQueue = localStorage.getItem("inQueue");
    const storedIsJoined = localStorage.getItem("isJoined");

    if (storedInQueue === "true") {
      setInQueue(true);
      setQueueStatus(storedQueueStatus || "Not in queue");
    }
    if (storedIsJoined === "true") {
      setIsJoined(true);
    }
  }, []);

  useEffect(() => {
    checkQueueStatus();
    const interval = setInterval(() => {
      checkQueueStatus();
    }, 5000); // Check every 5 seconds
    return () => clearInterval(interval);
  }, []);

  const checkQueueStatus = async () => {
    try {
      const response = await axios.get("/queue/status");
      setInQueue(response.data.in_queue);
      setQueueStatus(response.data.message || "Not in queue");
      setQueueCount(response.data.queue_count);
      setQueueTime(response.data.queue_time);
      localStorage.setItem("queueStatus", response.data.message || "Not in queue");
      localStorage.setItem("inQueue", response.data.in_queue.toString());
    } catch (error) {
      console.error("Error checking queue status:", error);
    }
  };

  const handleJoinQueue = async () => {
    try {
      const response = await axios.post("/queue/join");
      setQueueStatus(response.data.message);
      setInQueue(true);
      setIsJoined(true);
      localStorage.setItem("queueStatus", response.data.message);
      localStorage.setItem("inQueue", "true");
      localStorage.setItem("isJoined", "true");
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  const handleLeaveQueue = async () => {
    try {
      const response = await axios.post("/queue/leave");
      setQueueStatus(response.data.message);
      setInQueue(false);
      setIsJoined(false);
      localStorage.setItem("queueStatus", response.data.message);
      localStorage.setItem("inQueue", "false");
      localStorage.setItem("isJoined", "false");
    } catch (error) {
      setQueueStatus(error.response?.data?.message || "An error occurred.");
    }
  };

  const handleCreateLobby = async () => {
    try {
      const response = await axios.post("/lobby/create");
      if (response.data.redirect_url) {
        window.location.href = response.data.redirect_url;
      }
    } catch (error) {
      console.error("Error creating lobby:", error);
      alert("Failed to create lobby.");
    }
  };

  const handleCreateSoloGame = async () => {
    try {
        const response = await axios.post("/game/createSolo", { difficulty });
        console.log("Solo game creation response:", response.data); // Log the response
        localStorage.setItem("lastSoloGameId", response.data.redirect_url.split('/').pop());
        if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
        }
    } catch (error) {
        console.error("Error creating solo game:", error.response?.data || error);
        alert("Failed to create solo game: " + (error.response?.data?.message || "Unknown error"));
    }
}

  const handleJoinByInviteCode = async () => {
    if (!inviteCode) {
      alert("Please enter a valid invite code.");
      return;
    }
    try {
      const response = await axios.post("/lobby/join", { invite_code: inviteCode });
      if (response.data.redirect_url) {
        window.location.href = response.data.redirect_url;
      }
    } catch (error) {
      console.error("Error joining lobby:", error);
      alert("Failed to join lobby: " + (error.response?.data?.message || "Invalid invite code"));
    }
  };

  return (
    <AuthenticatedLayout>
      <motion.div 
        className="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-800 flex items-center justify-center p-8 relative overflow-hidden"
        variants={containerVariants}
        initial="hidden"
        animate="visible"
      >
        {/* Animated Background Elements */}
        <motion.div 
          className="absolute w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"
          animate={{ scale: [1, 1.2, 1], rotate: 360 }}
          transition={{ duration: 10, repeat: Infinity, ease: "linear" }}
          style={{ top: "-10%", left: "-10%" }}
        />
        <motion.div 
          className="absolute w-72 h-72 bg-blue-500/20 rounded-full blur-3xl"
          animate={{ scale: [1, 1.1, 1], rotate: -360 }}
          transition={{ duration: 12, repeat: Infinity, ease: "linear" }}
          style={{ bottom: "-5%", right: "-5%" }}
        />

        <motion.div 
          className="bg-gray-900/80 backdrop-blur-md rounded-xl p-8 shadow-2xl w-full max-w-lg border border-gray-700/50"
          variants={itemVariants}
        >
          <motion.h1 
            className="text-5xl font-extrabold text-center mb-6 bg-gradient-to-r from-purple-400 to-blue-500 bg-clip-text text-transparent"
            variants={itemVariants}
          >
            Welcome to Shithead!
          </motion.h1>

          <motion.p 
            className={`text-xl text-center mb-8 ${inQueue ? "text-yellow-400" : "text-green-400"}`}
            variants={itemVariants}
            animate={{ y: [0, -5, 0] }}
            transition={{ repeat: Infinity, duration: 2 }}
          >
            {queueStatus}
          </motion.p>

          {/* Buttons */}
          <motion.div className="flex flex-col items-center gap-4" variants={itemVariants}>
            {!isJoined && (
              <motion.button
                onClick={handleJoinQueue}
                className="w-64 py-4 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-lg hover:shadow-xl"
                variants={buttonVariants}
                whileHover="hover"
                whileTap="tap"
              >
                Join Quick Match
              </motion.button>
            )}
            <motion.button
              onClick={handleCreateLobby}
              className="w-64 py-4 bg-gradient-to-r from-purple-600 to-purple-800 text-white rounded-lg shadow-lg hover:shadow-xl"
              variants={buttonVariants}
              whileHover="hover"
              whileTap="tap"
            >
              Host Multiplayer
            </motion.button>
            <motion.div className="flex flex-col items-center gap-2" variants={itemVariants}>
              <select
                value={difficulty}
                onChange={(e) => setDifficulty(e.target.value)}
                className="w-64 py-2 px-4 bg-gray-800 text-white rounded-lg border border-gray-600 focus:ring-2 focus:ring-purple-500"
              >
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
              </select>
              <motion.button
                onClick={handleCreateSoloGame}
                className="w-64 py-4 bg-gradient-to-r from-green-600 to-green-800 text-white rounded-lg shadow-lg hover:shadow-xl"
                variants={buttonVariants}
                whileHover="hover"
                whileTap="tap"
              >
                Start Solo Game
              </motion.button>
            </motion.div>
            <motion.button
              onClick={() => setShowInviteInput(!showInviteInput)}
              className="w-64 py-4 bg-gradient-to-r from-indigo-600 to-indigo-800 text-white rounded-lg shadow-lg hover:shadow-xl"
              variants={buttonVariants}
              whileHover="hover"
              whileTap="tap"
            >
              {showInviteInput ? "Hide Invite" : "Join by Code"}
            </motion.button>
          </motion.div>

          {/* Invite Code Input */}
          {showInviteInput && (
            <motion.div 
              className="mt-6 text-center"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
            >
              <motion.h2 className="text-2xl mb-4 text-purple-300" variants={itemVariants}>
                Enter Invite Code
              </motion.h2>
              <input
                type="text"
                value={inviteCode}
                onChange={(e) => setInviteCode(e.target.value)}
                placeholder="Enter invite code"
                className="w-64 px-4 py-3 rounded-lg bg-gray-800 text-white border border-gray-600 focus:ring-2 focus:ring-purple-500 mb-4"
              />
              <motion.button
                onClick={handleJoinByInviteCode}
                className="w-64 py-4 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-lg shadow-lg hover:shadow-xl"
                variants={buttonVariants}
                whileHover="hover"
                whileTap="tap"
              >
                Join Lobby
              </motion.button>
            </motion.div>
          )}

          {/* Queue Info */}
          {inQueue && (
            <motion.div 
              className="mt-8 bg-gray-800/90 p-6 rounded-lg shadow-lg border border-gray-700/50 relative"
              variants={itemVariants}
            >
              <motion.h2 className="text-2xl mb-4 text-blue-300" variants={itemVariants}>
                Queue Information
              </motion.h2>
              <p className="text-lg text-gray-300">Players in queue: {queueCount}</p>
              {queueTime && <p className="text-lg text-gray-300">Time: {queueTime}s</p>}
              <motion.button
                onClick={handleLeaveQueue}
                className="mt-4 w-full py-4 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg shadow-lg hover:shadow-xl"
                variants={buttonVariants}
                whileHover="hover"
                whileTap="tap"
              >
                Leave Quick Match
              </motion.button>
            </motion.div>
          )}
        </motion.div>
      </motion.div>
    </AuthenticatedLayout>
  );
};

export default Dashboard;