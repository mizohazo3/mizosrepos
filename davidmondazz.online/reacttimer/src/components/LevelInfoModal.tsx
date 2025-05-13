import React from 'react';
import {
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalFooter,
  ModalBody,
  ModalCloseButton,
  Button,
  Table,
  Thead,
  Tbody,
  Tr,
  Th,
  Td,
  TableCaption,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';

// Import the defined level structure
import { levelStructure, LevelData } from '../config/levels';

interface LevelInfoModalProps {
  isOpen: boolean;
  onClose: () => void;
}

const formatHours = (hours: number): string => {
  return `${hours.toFixed(0)} hr`;
};

const LevelInfoModal: React.FC<LevelInfoModalProps> = ({ isOpen, onClose }) => {
  // No need for useMemo calculation, just use the imported structure
  // (excluding the placeholder level 0)
  const levelsToDisplay = levelStructure.slice(1);

  const tableBg = useColorModeValue('gray.50', 'gray.700');

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl" scrollBehavior="inside">
      <ModalOverlay />
      <ModalContent>
        <ModalHeader>Level & Rate Structure</ModalHeader>
        <ModalCloseButton />
        <ModalBody pb={6}>
          <VStack spacing={4} align="stretch">
            <Text>
              Level up by accumulating total tracked time across all timers.
              Your hourly rate increases as you reach higher level tiers.
            </Text>
            <Table variant="simple" size="sm" bg={tableBg} borderRadius="md">
              <TableCaption placement="top">Level Milestones</TableCaption>
              <Thead>
                <Tr>
                  <Th isNumeric>Level</Th>
                  <Th>Title</Th>
                  <Th isNumeric>Rate/hr</Th>
                  <Th>Total Time Required</Th>
                </Tr>
              </Thead>
              <Tbody>
                {levelsToDisplay.map((item) => (
                  <Tr key={item.level}>
                    <Td isNumeric>{item.level}</Td>
                    <Td>{item.title}</Td>
                    <Td isNumeric>${item.ratePerHour.toFixed(2)}</Td>
                    <Td>{formatHours(item.cumulativeHoursRequired)}</Td>
                  </Tr>
                ))}
              </Tbody>
            </Table>
            <Text fontSize="sm" color="gray.500">
              Maximum level is 100.
            </Text>
          </VStack>
        </ModalBody>
        <ModalFooter>
          <Button onClick={onClose}>Close</Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
};

export default LevelInfoModal; 