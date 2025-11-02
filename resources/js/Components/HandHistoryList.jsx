import { useEffect, useState } from "react";
import HandHistoryItem from "./HandHistoryItem";
import LoadingSpinner from "./LoadingSpinner";
function HandHistoryList() {
    const [handHistories, setHandHistories] = useState([]);
    const [loading, setLoading] = useState(true);

    const getHandHistories = async () => {
        try {
            const response = await axios.get('/hands')
            setHandHistories(response.data);
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getHandHistories();
    }, []);
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : handHistories.length > 0 ? (
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
                        {handHistories.map((history, index) => (
                            <HandHistoryItem key={index} historyItem={history} />
                        ))}         
                    </tbody>

                </table>
            ) : (
                <p>No Hand histories found</p>
            )}
        </div>
    )
}

export default HandHistoryList;