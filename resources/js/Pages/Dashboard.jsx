import React, { useEffect, useState } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

const Dashboard = () => {
    const [queueStatus, setQueueStatus] = useState("Not in queue");
    const [inQueue, setInQueue] = useState(false);
    const [inviteCode, setInviteCode] = useState("");
    const [showInviteInput, setShowInviteInput] = useState(false);
    const [queueTime, setQueueTime] = useState(null);
    const [queueCount, setQueueCount] = useState(0);

    const [isJoined, setIsJoined] = useState(false);

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

    // Функция для проверки статуса очереди
    const checkQueueStatus = async () => {
        try {
            const response = await axios.get("/queue/status");
            setInQueue(response.data.in_queue);
            setQueueStatus(response.data.message || "Not in queue");
            setQueueCount(response.data.queue_count); // Обновляем количество людей в очереди
            setQueueTime(response.data.queue_time);

            // Сохраняем данные в localStorage
            localStorage.setItem("queueStatus", response.data.message || "Not in queue");
            localStorage.setItem("inQueue", response.data.in_queue.toString());
        } catch (error) {
            console.error("Error checking queue status:", error);
        }
    };

    // Используем useEffect для первоначальной проверки статуса очереди
    useEffect(() => {
        checkQueueStatus();
    }, []);

    // Функция для обновления информации о очереди
    useEffect(() => {
        const fetchQueue = async () => {
            try {
                const response = await axios.get("/queue");
                setQueueCount(response.data.queue_count); // Обновляем количество людей в очереди
                setQueueTime(response.data.queue_time);
            } catch (error) {
                console.error("Error fetching queue:", error);
            }
        };

        const interval = setInterval(fetchQueue, 1000);
        return () => clearInterval(interval);
    }, []);

    const handleJoinQueue = async () => {
        try {
            const response = await axios.post("/queue/join");
            setQueueStatus(response.data.message);
            setInQueue(true);
            setIsJoined(true);

            // Сохраняем данные в localStorage
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

            // Сохраняем данные в localStorage
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
            console.log("Lobby created:", response.data);
        } catch (error) {
            console.error("Error creating lobby:", error);
        }
    };

    const handleCreateSoloGame = async () => {
        try {
            const response = await axios.post("/game/createSolo");

            if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            }
        } catch (error) {
            console.error("Error creating solo game:", error);
            alert("Failed to create solo game. Please try again.");
        }
    };

    const handleJoinByInviteCode = async () => {
        if (!inviteCode) {
            alert("Please enter a valid invite code.");
            return;
        }

        try {
            const response = await axios.post("/lobby/join", {
                invite_code: inviteCode,
            });
            if (response.data.success) {
                window.location.href = `/lobby/${response.data.game_id}`;
            } else {
                alert("Failed to join the lobby. Invalid invite code.");
            }
        } catch (error) {
            console.error("Error joining Lobby:", error);
            alert("An error occurred while joining the Lobby.");
        }
    };

    return (
        <AuthenticatedLayout>
            <div className="bg-gray-900 text-white p-8 rounded-xl relative">
                <h1 className="text-4xl text-center mb-4">
                    Welcome to Shithead!
                </h1>
                <p
                    className={`text-xl text-center mb-6 ${
                        inQueue ? "text-yellow-400" : "text-green-500"
                    }`}
                >
                    {queueStatus}
                </p>

                {/* Кнопки расположены вертикально по центру с полупрозрачным серым цветом */}
                <div className="flex flex-col items-center gap-4 mb-6">
                    {!isJoined && (
                        <button
                            onClick={handleJoinQueue}
                            className="px-8 py-4 bg-gray-500/75 hover:bg-gray-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-500"
                        >
                            Join Quick Match
                        </button>
                    )}
                    <button
                        onClick={handleCreateLobby}
                        className="px-8 py-4 bg-gray-500/75 hover:bg-gray-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-500"
                    >
                        Host Multiplayer Lobby
                    </button>
                    <button
                        onClick={handleCreateSoloGame}
                        className="px-8 py-4 bg-gray-500/75 hover:bg-gray-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-500"
                    >
                        Make Solo Game
                    </button>
                    <button
                        onClick={() => setShowInviteInput(!showInviteInput)}
                        className="px-8 py-4 bg-gray-500/75 hover:bg-gray-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-500"
                    >
                        {showInviteInput
                            ? "Close Invite Input"
                            : "Join by Invite Code"}
                    </button>
                </div>

                {/* Join by Invite Code */}
                {showInviteInput && (
                    <div className="mt-8 text-center">
                        <h2 className="text-2xl mb-4">Enter Invite Code</h2>
                        <input
                            type="text"
                            value={inviteCode}
                            onChange={(e) => setInviteCode(e.target.value)}
                            placeholder="Enter invite code"
                            className="px-6 py-3 rounded-lg bg-gray-700 text-white border-none mb-4 focus:ring-2 focus:ring-purple-500"
                        />
                        <button
                            onClick={handleJoinByInviteCode}
                            className="px-8 py-4 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-purple-500"
                        >
                            Join Lobby
                        </button>
                    </div>
                )}

                {/* Queue Information */}
                {inQueue && (
                    <div className="mt-8 text-center bg-gray-800 p-6 rounded-lg shadow-md relative">
                        <h2 className="text-2xl mb-4">Queue Information</h2>
                        <p className="text-lg">People in queue: {queueCount}</p>
                        {/* Кнопка "Leave Quick Match" расположена внизу рядом с очередью */}
                        <button
                            onClick={handleLeaveQueue}
                            className="px-8 py-4 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-red-500 absolute bottom-4 left-4"
                        >
                            Leave Quick Match
                        </button>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
};

export default Dashboard;
