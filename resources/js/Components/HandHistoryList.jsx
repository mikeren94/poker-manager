import { useEffect, useState } from "react";

function HandHistoryList() {
    const [handHistories, setHandHistories] = useState([]);


    const getHandHistories = async () => {
        try {
            const response = await axios.get('/api/hands')
            console.log(response.data);
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
                <p>List histories</p>
            ) : (
                <p>No Hand histories found</p>
            )}
        </div>
    )
}

export default HandHistoryList;