import React from 'react';
import { Container, SlideFade } from '@chakra-ui/react';
import TimerList from '../components/TimerList';

const HomePage: React.FC = () => {
  return (
    <Container maxW="container.xl" py={8}>
      <SlideFade in={true} offsetY="20px">
        <TimerList />
      </SlideFade>
    </Container>
  );
};

export default HomePage;