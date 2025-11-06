import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import SessionItem from './SessionItem';
import HandItem from "./HandItem";


function HandTable({session}) {
    const [hands, setHands] = useState([]);
    const [loading, setLoading] = useState(true);

    const getHands = async () => {
        try {
            const response = await axios.get(`/sessions/${session.id}/hands`)
            setHands(response.data);
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getHands();
    }, []);
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : hands.length > 0 ? (
                <table className="table-auto w-full">
                    <thead>
                        <tr>
                            <td>Hand</td>
                            <td>Result</td>
                            <td>Flop</td>
                            <td>Turn</td>
                            <td>River</td>
                        </tr>
                    </thead>
                    <tbody>
                        {hands.map((hand, index) => (
                            <HandItem key={index} historyItem={hand} />
                        ))}         
                    </tbody>

                </table>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default HandTable;