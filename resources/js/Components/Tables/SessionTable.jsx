import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import SessionItem from './SessionItem';

function SessionTable() {
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);

    const getSessions = async () => {
        try {
            const response = await axios.get('/sessions')
            setSessions(response.data);
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getSessions();
    }, []);
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : sessions.length > 0 ? (
                <table className="table-auto w-full">
                    <thead>
                        <tr>
                            <td>Session</td>
                            <td>Stakes</td>
                            <td>Time</td>
                            <td>Result</td>
                        </tr>
                    </thead>
                    <tbody>
                        {sessions.map((session, index) => (
                            <SessionItem key={index} session={session}/>
                        ))}         
                    </tbody>

                </table>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default SessionTable;