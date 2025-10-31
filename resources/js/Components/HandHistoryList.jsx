import { useEffect, useState } from "react";
import HandHistoryItem from "./HandHistoryItem";

function HandHistoryList() {
    const [handHistories, setHandHistories] = useState([]);


    const getHandHistories = async () => {
        try {
            const response = await axios.get('/hands')
            setHandHistories(response.data);
            console.log(handHistories);
        } catch (error) {
            console.log(error)
        }
    }

    useEffect(() => {
        getHandHistories();
    }, []);
    return (
        <div>
            {handHistories.length > 0 ? (
                <ul>
                    {handHistories.map((history, index) => (
                       <li key={index} className="row-container">
                            <HandHistoryItem historyItem={history} />
                        </li> 
                    ))}
                </ul>
            ) : (
                <p>No Hand histories found</p>
            )}
        </div>
    )
}

export default HandHistoryList;