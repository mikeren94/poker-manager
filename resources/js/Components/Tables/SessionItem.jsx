import { router } from '@inertiajs/react';

function SessionItem({session}) {
        return (
        <tr 
            className="border-b cursor-pointer hover:bg-gray-100 transition"
            onClick={() => router.visit(`/sessions/${session.id}`)}   
        >
            <td>
                {session.session_id}
            </td>
            <td>          
                {session.type}
            </td>
            <td>
                {session.start_time}
            </td>
            <td>
                <span className={`font-bold font-mono text-base ${session.result > 0 ? 'text-green-600' : 'text-red-500'}`}>
                    {session.result}
                </span>                
                {}
            </td>
        </tr>
    )
}

export default SessionItem;