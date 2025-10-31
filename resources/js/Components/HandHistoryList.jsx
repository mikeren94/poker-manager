import { useEffect, useState } from "react";

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
    })
    return (
        <div>
            {handHistories.length > 0 ? (
                <ul>
                    {handHistories.map((history, index) => (
                       <li key={index}>{history.hand_players[0].result}</li> 
                    ))}
                </ul>
            ) : (
                <p>No Hand histories found</p>
            )}
        </div>
    )
}

export default HandHistoryList;