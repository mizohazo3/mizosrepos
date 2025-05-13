import React, { useState, useEffect } from 'react';
import {
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalFooter,
  ModalBody,
  ModalCloseButton,
  Button,
  Input,
  Text,
  VStack,
  useToast,
} from '@chakra-ui/react';

interface DeleteConfirmationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => Promise<void>; // Make confirm async
  timerName: string | null;
}

const CONFIRM_TEXT = 'Delete';

const DeleteConfirmationModal: React.FC<DeleteConfirmationModalProps> = ({ isOpen, onClose, onConfirm, timerName }) => {
  const [inputValue, setInputValue] = useState('');
  const [isDeleting, setIsDeleting] = useState(false);
  const toast = useToast();

  // Reset input when modal opens or closes
  useEffect(() => {
    if (!isOpen) {
      setInputValue('');
      setIsDeleting(false);
    }
  }, [isOpen]);

  const isMatch = inputValue === CONFIRM_TEXT;

  const handleConfirmClick = async () => {
    if (!isMatch) return;
    setIsDeleting(true);
    try {
      await onConfirm();
      toast({
        title: 'Timer deleted',
        description: `Timer "${timerName}" was successfully deleted.`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
      onClose(); // Close modal on success
    } catch (error) {
      console.error("Deletion failed:", error);
      toast({
        title: 'Deletion Failed',
        description: 'Could not delete the timer. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} isCentered>
      <ModalOverlay />
      <ModalContent>
        <ModalHeader>Confirm Deletion</ModalHeader>
        <ModalCloseButton />
        <ModalBody pb={6}>
          <VStack spacing={4} align="stretch">
            <Text>
              Are you sure you want to delete the timer
              <Text as="span" fontWeight="bold"> "{timerName || ''}"</Text>?
              This action cannot be undone.
            </Text>
            <Text>
              To confirm, please type "<Text as="span" fontWeight="bold">{CONFIRM_TEXT}</Text>" in the box below:
            </Text>
            <Input
              placeholder={CONFIRM_TEXT}
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              autoFocus // Focus the input when modal opens
            />
          </VStack>
        </ModalBody>
        <ModalFooter>
          <Button variant="ghost" mr={3} onClick={onClose} isDisabled={isDeleting}>
            Cancel
          </Button>
          <Button
            colorScheme="red"
            onClick={handleConfirmClick}
            isDisabled={!isMatch || isDeleting}
            isLoading={isDeleting}
          >
            Delete Timer
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
};

export default DeleteConfirmationModal; 