import { MessageService } from '../services/message.service';

export function useMessages() {
    return {
        inbox: MessageService.inbox,
        create: MessageService.create,
        adminList: MessageService.adminList,
        adminReply: MessageService.adminReply,
        adminResolve: MessageService.adminResolve,
        adminReject: MessageService.adminReject,
    };
}
